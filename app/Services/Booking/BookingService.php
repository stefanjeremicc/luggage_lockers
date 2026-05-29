<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Jobs\CreateTTLockAccessCode;
use App\Jobs\DeleteTTLockAccessCode;
use App\Jobs\SendBookingCancelled;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingLocker;
use App\Models\Locker;
use App\Services\Lock\TtlockQuota;
use App\Services\Notification\BookingNotifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private PricingService $pricingService,
    ) {}

    public function create(array $data): Booking
    {
        $booking = DB::transaction(function () use ($data) {
            // Normalise legacy (locker_size + locker_qty) → items[]. Multi-size bookings
            // come in as $data['items'] = [{size, qty, duration?}, ...]. Each item may
            // optionally specify its own duration; otherwise the global $data['duration']
            // applies to all items (legacy single-window mode).
            $items = $data['items'] ?? [[
                'size' => $data['locker_size'] ?? 'standard',
                'qty' => (int) ($data['locker_qty'] ?? 1),
            ]];
            // When the TTLock quota is exhausted we operate in permanent-PIN mode:
            // assign each locker its fixed permanent code (no TTLock call) and only
            // consider lockers that actually have one.
            $quotaExceeded = TtlockQuota::isExceeded();

            $items = array_values(array_filter(
                $items,
                fn($i) => isset($i['size'], $i['qty']) && (int) $i['qty'] > 0
            ));
            if (empty($items)) {
                throw new \RuntimeException('No locker items selected.');
            }

            $globalDuration = $data['duration'] ?? null;

            // User enters local (Belgrade) date+time; parse in display_timezone, then
            // convert to UTC. Each item gets its own check_in/check_out based on its
            // duration; the parent booking row stores the earliest/latest as legacy
            // aggregates so range queries on `bookings` keep working.
            $baseCheckIn = Carbon::parse("{$data['date']} {$data['time']}", config('app.display_timezone'))
                ->setTimezone('UTC');

            $itemWindows = [];
            foreach ($items as $i => $item) {
                $itemDuration = $item['duration'] ?? $globalDuration;
                if (!$itemDuration) {
                    throw new \RuntimeException("Missing duration for {$item['size']}");
                }
                $itemWindows[$i] = [
                    'duration' => $itemDuration,
                    'check_in' => $baseCheckIn->copy(),
                    'check_out' => $this->availabilityService->calculateCheckOut($baseCheckIn, $itemDuration),
                ];
            }

            $pricing = $this->pricingService->calculateForItems(
                $data['location_id'],
                $items,
                $globalDuration
            );
            if (isset($pricing['error'])) {
                throw new \RuntimeException($pricing['error']);
            }

            // Per-item availability + locker selection. Each item is checked in its own
            // window; we hold a lock on the locker rows we intend to take so concurrent
            // bookings can't race us.
            $totalQty = 0;
            $primarySize = null;
            $primaryQty = 0;
            $earliestCheckIn = null;
            $latestCheckOut = null;
            $resolvedItems = []; // per-item: [size, qty, duration, check_in, check_out, free_lockers]

            foreach ($items as $i => $item) {
                $size = $item['size'];
                $qty = (int) $item['qty'];
                $totalQty += $qty;
                $w = $itemWindows[$i];

                $earliestCheckIn = $earliestCheckIn ? $earliestCheckIn->min($w['check_in']) : $w['check_in']->copy();
                $latestCheckOut = $latestCheckOut ? $latestCheckOut->max($w['check_out']) : $w['check_out']->copy();

                $candidates = Locker::where('location_id', $data['location_id'])
                    ->where('size', $size)
                    ->bookable()
                    // In permanent-PIN mode, only lockers that have a permanent code
                    // can be booked (others have no working code to hand out).
                    ->when($quotaExceeded, fn($q) => $q->whereNotNull('permanent_pin')->where('permanent_pin', '!=', ''))
                    ->orderByRaw('last_used_at IS NULL DESC')
                    ->orderBy('last_used_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $bookedLockerIds = $this->getBookedLockerIds(
                    $data['location_id'], $size, $w['check_in'], $w['check_out']
                );
                $free = $candidates->reject(fn($l) => in_array($l->id, $bookedLockerIds))->take($qty);

                if ($free->count() < $qty) {
                    $availableCount = $candidates->count() - count($bookedLockerIds);
                    throw new \App\Exceptions\NotAvailableException(
                        "Sorry, only {$availableCount} {$size} locker(s) are available for this slot.",
                        []
                    );
                }

                $resolvedItems[] = [
                    'size' => $size,
                    'qty' => $qty,
                    'duration' => $w['duration'],
                    'check_in' => $w['check_in'],
                    'check_out' => $w['check_out'],
                    'lockers' => $free,
                ];

                if ($qty > $primaryQty) { $primaryQty = $qty; $primarySize = $size; }
            }

            // Booking row keeps a "primary" size for legacy display (size with the most
            // lockers). check_in/check_out store the earliest start / latest end across
            // all items so existing date-range queries remain meaningful.
            $booking = Booking::create([
                'uuid' => Str::uuid(),
                'customer_id' => $data['customer_id'],
                'location_id' => $data['location_id'],
                'locker_size' => $primarySize ?: 'standard',
                'locker_qty' => $totalQty,
                'check_in' => $earliestCheckIn,
                'check_out' => $latestCheckOut,
                'duration_label' => $globalDuration ?: $resolvedItems[0]['duration'],
                'price_eur' => $pricing['subtotal_eur'],
                'price_rsd' => $pricing['total_rsd'],
                'service_fee_eur' => $pricing['service_fee_eur'],
                'total_eur' => $pricing['total_eur'],
                'booking_status' => BookingStatus::Confirmed,
                'payment_status' => PaymentStatus::Pending,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ] + $this->attributionAttributes($data['attribution'] ?? []));

            // Persist per-item rows (booking_items) with their own windows; link each
            // booking_locker to its parent item so reschedules and per-item operations
            // stay scoped correctly.
            $priceLines = collect($pricing['lines'] ?? []);
            $bookingLockerIds = [];

            foreach ($resolvedItems as $r) {
                $line = $priceLines->first(fn($l) => $l['size'] === $r['size'] && $l['duration'] === $r['duration']);
                $unit = (float) ($line['unit_price_eur'] ?? 0);

                $item = BookingItem::create([
                    'booking_id' => $booking->id,
                    'locker_size' => $r['size'],
                    'qty' => $r['qty'],
                    'duration_key' => $r['duration'],
                    'check_in' => $r['check_in'],
                    'check_out' => $r['check_out'],
                    'unit_price_eur' => $unit,
                    'line_total_eur' => $unit * $r['qty'],
                ]);

                foreach ($r['lockers'] as $locker) {
                    // Permanent-PIN mode: use the locker's fixed code (no TTLock).
                    // Otherwise generate a fresh timed PIN to register on the lock.
                    $pin = ($quotaExceeded && $locker->permanent_pin)
                        ? $locker->permanent_pin
                        : $this->generatePin();
                    $bl = BookingLocker::create([
                        'booking_id' => $booking->id,
                        'booking_item_id' => $item->id,
                        'locker_id' => $locker->id,
                        'pin_code_encrypted' => Crypt::encryptString($pin),
                        'assigned_at' => now(),
                    ]);
                    $bookingLockerIds[] = $bl->id;
                    $locker->update(['last_used_at' => now()]);
                }
            }

            $booking->load('lockers', 'location', 'customer', 'items');

            return $booking;
        });

        // Register every locker's PIN with TTLock cloud synchronously, then send
        // ONE combined customer email + one admin alert. The customer should see
        // a working PIN in TTLock and a confirmation in their inbox within a few
        // seconds of the booking POST returning, not after a queue tick.
        //
        // Each sync call is guarded — a transient TTLock outage falls back to the
        // queued retry/backoff path so we never roll back a paid booking.
        // Permanent-PIN mode: the PIN is already the locker's fixed code and the
        // lock knows it — do NOT call TTLock at all (no add, nothing to delete later).
        if (!TtlockQuota::isExceeded()) {
            $bookingLockerIds = BookingLocker::where('booking_id', $booking->id)->pluck('id');
            foreach ($bookingLockerIds as $blId) {
                try {
                    CreateTTLockAccessCode::dispatchSync($blId);
                } catch (\App\Exceptions\TtlockQuotaExceededException $e) {
                    // Quota flipped mid-request: leave the generated PIN as-is and
                    // skip — the booking still succeeds, code handled next month.
                    \Log::warning('TTLock quota exceeded mid-booking; skipped registration', [
                        'booking_locker_id' => $blId,
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Sync TTLock PIN registration failed, falling back to queue', [
                        'booking_locker_id' => $blId, 'error' => $e->getMessage(),
                    ]);
                    CreateTTLockAccessCode::dispatch($blId);
                }
            }
        }

        // Send the customer confirmation + admin alert after every locker has
        // been processed (success or queue-fallback). buildVars decrypts PINs
        // straight from booking_lockers, so the email matches what's in TTLock.
        try {
            BookingNotifier::sendBookingConfirmation($booking->fresh(['customer', 'location', 'bookingLockers.locker']));
        } catch (\Throwable $e) {
            \Log::error('Customer booking_confirmed send failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }
        try {
            BookingNotifier::sendBookingConfirmedAdmin($booking);
        } catch (\Throwable $e) {
            \Log::error('Admin booking_confirmed send failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }

        return $booking;
    }

    /**
     * Find locker IDs already taken at the given location, size, and time window.
     * Source of truth is now booking_items (post-Block-A migration backfilled all
     * legacy bookings). Each item carries its own check_in/check_out, so per-size
     * + per-window overlap detection is exact even when a booking mixes sizes
     * with different durations.
     */
    private function getBookedLockerIds(int $locationId, string $size, Carbon $checkIn, Carbon $checkOut): array
    {
        return BookingLocker::query()
            ->whereNotNull('booking_item_id')
            ->whereHas('bookingItem', function ($q) use ($size, $checkIn, $checkOut) {
                $q->where('locker_size', $size)
                    ->where('check_in', '<', $checkOut)
                    ->where('check_out', '>', $checkIn);
            })
            ->whereHas('booking', function ($q) use ($locationId) {
                $q->where('location_id', $locationId)
                    ->whereIn('booking_status', [BookingStatus::Confirmed, BookingStatus::Active]);
            })
            ->pluck('locker_id')
            ->toArray();
    }

    public function generatePin(): string
    {
        // 4-digit numeric PIN (TTLock supports 4–9 digits). Avoid collision with PINs
        // currently active on any non-final booking — TTLock rejects duplicates per lock.
        $active = BookingLocker::whereHas('booking', function ($q) {
            $q->whereIn('booking_status', [BookingStatus::Confirmed, BookingStatus::Active]);
        })->pluck('pin_code_encrypted');

        $taken = [];
        foreach ($active as $enc) {
            try { $taken[Crypt::decryptString($enc)] = true; } catch (\Throwable) { /* skip */ }
        }

        for ($i = 0; $i < 50; $i++) {
            $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            // Avoid trivial codes (0000, 1111, 1234) — easily guessed.
            if (in_array($pin, ['0000', '1111', '2222', '3333', '4444', '5555', '6666', '7777', '8888', '9999', '1234', '4321'], true)) {
                continue;
            }
            if (!isset($taken[$pin])) return $pin;
        }
        // Fallback — extremely unlikely with 10k space and a small number of active bookings.
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  string  $initiatedBy  'customer' (self-service cancel link) or 'admin' (cancel from dashboard).
     *                               Controls which cancellation email template is rendered.
     */
    public function cancel(Booking $booking, ?string $reason = null, string $initiatedBy = 'customer'): Booking
    {
        $booking->update([
            'booking_status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        // Run TTLock cleanup + customer email SYNCHRONOUSLY so admin sees the
        // outcome in the same HTTP round-trip. Customer also gets the email
        // before the response returns, eliminating the queue-tick delay.
        // Each leg is wrapped in try/catch so a transient TTLock or SMTP
        // failure falls back to the queued retry path rather than rolling
        // back a cancel that the admin already committed to.
        foreach (BookingLocker::where('booking_id', $booking->id)->whereNotNull('ttlock_keyboard_pwd_id')->pluck('id') as $blId) {
            try {
                DeleteTTLockAccessCode::dispatchSync($blId);
            } catch (\Throwable $e) {
                Log::error('Sync TTLock delete on cancel failed; queued for retry', [
                    'booking_locker_id' => $blId, 'error' => $e->getMessage(),
                ]);
                DeleteTTLockAccessCode::dispatch($blId);
            }
        }

        $templateKey = $initiatedBy === 'admin'
            ? 'booking_cancelled_by_admin'
            : 'booking_cancelled_by_customer';

        try {
            SendBookingCancelled::dispatchSync($booking->id, $templateKey);
        } catch (\Throwable $e) {
            Log::error('Sync cancellation email failed; queued for retry', [
                'booking_id' => $booking->id, 'error' => $e->getMessage(),
            ]);
            SendBookingCancelled::dispatch($booking->id, $templateKey);
        }

        // Admin gets its own concise alert (different content from the
        // customer-facing apology email). Sent sync alongside, but a failure
        // here is logged + ignored — admin notification is a nice-to-have, not
        // worth bubbling up as a 500 to the user/admin who already cancelled.
        try {
            BookingNotifier::sendBookingCancelledAdmin($booking->fresh(['customer','location','bookingLockers.locker']), $initiatedBy);
        } catch (\Throwable $e) {
            Log::error('Admin cancellation alert failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }

        return $booking;
    }

    /**
     * Remove one or more lockers from a booking WITHOUT touching the rest.
     *
     * Hard safety guarantees (this method can never affect another booking):
     *  - Targets are loaded scoped to THIS booking's id; ids from other bookings
     *    are silently ignored.
     *  - All DB writes run inside a single transaction (atomic; rolls back on error).
     *  - Permanent PINs (locker.permanent_pin) are NEVER modified — only the
     *    booking↔locker association row is deleted, freeing the locker.
     *  - One-time TTLock codes are deleted ONLY when ttlock_keyboard_pwd_id is set
     *    (so it works in permanent-PIN mode now AND one-time mode after quota reset).
     *  - If the caller selects EVERY locker, we delegate to the proven cancel()
     *    path (full cancellation) instead of leaving an empty booking.
     *  - Pricing is recomputed with the SAME PricingService used by create().
     *
     * @param  int[]  $bookingLockerIds
     * @return array{action:string,removed:int,booking:Booking}
     */
    public function removeLockers(Booking $booking, array $bookingLockerIds, string $initiatedBy = 'admin', bool $notify = true): array
    {
        $status = $booking->booking_status instanceof BookingStatus
            ? $booking->booking_status->value
            : $booking->booking_status;
        if ($status === BookingStatus::Cancelled->value) {
            throw new \RuntimeException('Booking is already cancelled.');
        }

        // Scope strictly to THIS booking — ids belonging to any other booking
        // are dropped here, so the operation is physically incapable of touching
        // a different reservation.
        $targets = BookingLocker::with('locker')
            ->where('booking_id', $booking->id)
            ->whereIn('id', $bookingLockerIds)
            ->get();

        if ($targets->isEmpty()) {
            throw new \RuntimeException('None of the selected lockers belong to this booking.');
        }

        $totalOnBooking = BookingLocker::where('booking_id', $booking->id)->count();

        // Selecting every locker == cancel the whole booking → reuse the existing,
        // battle-tested cancel() path rather than emptying the booking.
        if ($targets->count() >= $totalOnBooking) {
            $this->cancel($booking, 'Cancelled by admin (all lockers removed)', $initiatedBy);
            return ['action' => 'cancelled', 'removed' => $targets->count(), 'booking' => $booking->fresh()];
        }

        $removedNumbers = $targets->map(fn ($t) => $t->locker?->number)->filter()->values()->implode(', ');

        DB::transaction(function () use ($booking, $targets) {
            foreach ($targets as $bl) {
                // One-time mode: delete the per-booking TTLock code so it stops
                // working. Permanent mode (ttlock_keyboard_pwd_id null): there is
                // nothing per-booking to delete and we must NOT touch the locker's
                // shared permanent PIN.
                if ($bl->ttlock_keyboard_pwd_id) {
                    try {
                        DeleteTTLockAccessCode::dispatchSync($bl->id);
                    } catch (\Throwable $e) {
                        Log::error('Sync TTLock delete on locker-remove failed; queued for retry', [
                            'booking_locker_id' => $bl->id, 'error' => $e->getMessage(),
                        ]);
                        DeleteTTLockAccessCode::dispatch($bl->id);
                    }
                }
                $bl->delete(); // frees the locker for that window; permanent_pin untouched
            }

            // Recompute each booking_item's qty from the SURVIVING booking_lockers,
            // delete emptied items, then re-price the whole booking the same way
            // create() does (PricingService) so totals can never drift.
            $surviving = BookingLocker::where('booking_id', $booking->id)->get();
            $byItem = $surviving->groupBy('booking_item_id');

            foreach ($booking->items()->get() as $item) {
                $cnt = $byItem->has($item->id) ? $byItem->get($item->id)->count() : 0;
                if ($cnt === 0) {
                    $item->delete();
                } elseif ((int) $item->qty !== $cnt) {
                    $item->update([
                        'qty' => $cnt,
                        'line_total_eur' => (float) $item->unit_price_eur * $cnt,
                    ]);
                }
            }

            $items = $booking->items()->get()->map(fn ($it) => [
                'size' => $it->locker_size instanceof LockerSize ? $it->locker_size->value : $it->locker_size,
                'qty' => (int) $it->qty,
                'duration' => $it->duration_key,
            ])->all();

            $update = ['locker_qty' => $surviving->count()];

            if (!empty($items)) {
                $pricing = $this->pricingService->calculateForItems($booking->location_id, $items, $booking->duration_label);
                if (!isset($pricing['error'])) {
                    $update['price_eur'] = $pricing['subtotal_eur'];
                    $update['service_fee_eur'] = $pricing['service_fee_eur'];
                    $update['total_eur'] = $pricing['total_eur'];
                    $update['price_rsd'] = $pricing['total_rsd'];
                }
                // Aggregate window may shrink if a whole item was removed.
                $minIn = $booking->items()->min('check_in');
                $maxOut = $booking->items()->max('check_out');
                if ($minIn) { $update['check_in'] = $minIn; }
                if ($maxOut) { $update['check_out'] = $maxOut; }
            }

            $booking->update($update);
        });

        // Audit trail in the booking notes.
        $note = trim(($booking->notes ? $booking->notes . "\n" : '')
            . '[' . now()->toDateTimeString() . "] Removed locker(s): {$removedNumbers} by {$initiatedBy}.");
        $booking->update(['notes' => $note]);

        // Send the guest a refreshed confirmation: it re-renders from the
        // remaining booking_lockers, so it shows only the lockers/PINs that are
        // still valid and the new total.
        if ($notify) {
            try {
                \App\Jobs\SendBookingConfirmation::dispatchSync($booking->id);
            } catch (\Throwable $e) {
                Log::error('Updated confirmation after locker-remove failed; queued', [
                    'booking_id' => $booking->id, 'error' => $e->getMessage(),
                ]);
                \App\Jobs\SendBookingConfirmation::dispatch($booking->id);
            }
        }

        return [
            'action' => 'removed',
            'removed' => $targets->count(),
            'booking' => $booking->fresh(['lockers', 'items', 'customer', 'location']),
        ];
    }

    /**
     * Build the attribution columns for a new booking from the client-supplied
     * first-touch payload. Derives a normalised marketing_source channel and
     * keeps the raw UTM/referrer values for auditing.
     *
     * @param  array<string,mixed>  $attr
     * @return array<string,?string>
     */
    private function attributionAttributes(array $attr): array
    {
        $clean = fn($v, $max) => ($v = trim((string) ($v ?? ''))) === '' ? null : mb_substr($v, 0, $max);

        return [
            'marketing_source' => $this->deriveMarketingSource($attr),
            'utm_source'       => $clean($attr['utm_source'] ?? null, 100),
            'utm_medium'       => $clean($attr['utm_medium'] ?? null, 100),
            'utm_campaign'     => $clean($attr['utm_campaign'] ?? null, 150),
            'referrer'         => $clean($attr['referrer'] ?? null, 500),
            'landing_page'     => $clean($attr['landing_page'] ?? null, 500),
        ];
    }

    /**
     * Classify the first-touch source into a single channel:
     * google_ads | facebook | organic | referral | direct | other.
     */
    private function deriveMarketingSource(array $attr): string
    {
        $source   = strtolower(trim((string) ($attr['utm_source'] ?? '')));
        $medium   = strtolower(trim((string) ($attr['utm_medium'] ?? '')));
        $gclid    = trim((string) ($attr['gclid'] ?? ''));
        $fbclid   = trim((string) ($attr['fbclid'] ?? ''));
        $referrer = strtolower(trim((string) ($attr['referrer'] ?? '')));
        $paid     = in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch', 'paid_search', 'cpm'], true);

        // Paid click IDs are the strongest signal.
        if ($gclid !== '' || ($paid && str_contains($source, 'google')) || str_contains($source, 'adwords') || str_contains($source, 'googleads')) {
            return 'google_ads';
        }
        if ($fbclid !== '' || in_array($source, ['facebook', 'fb', 'instagram', 'ig', 'meta'], true) || str_contains($referrer, 'facebook.') || str_contains($referrer, 'instagram.')) {
            return 'facebook';
        }

        // Offline / printed QR-code links. We tag those URLs with utm_source=qr
        // (or an offline-ish medium) so foot-traffic from posters/stickers shows
        // as its own channel instead of being lumped into "referral".
        if (str_contains($source, 'qr') || str_contains($medium, 'qr')
            || in_array($medium, ['print', 'offline', 'flyer', 'poster', 'sticker'], true)) {
            return 'qr';
        }

        // UTM source set but not paid → treat as referral/campaign traffic.
        if ($source !== '') {
            if (in_array($source, ['google', 'bing', 'yahoo', 'duckduckgo', 'ecosia'], true) && !$paid) {
                return 'organic';
            }
            return 'referral';
        }

        // No UTM: fall back to the referrer host.
        if ($referrer !== '') {
            $host = parse_url($referrer, PHP_URL_HOST) ?: '';
            $engines = ['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'ecosia.', 'yandex.', 'baidu.'];
            foreach ($engines as $e) {
                if (str_contains($host, $e)) {
                    return 'organic';
                }
            }
            return 'referral';
        }

        // No UTM, no referrer → typed/bookmarked/app.
        return 'direct';
    }
}
