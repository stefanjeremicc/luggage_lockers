<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Jobs\CreateTTLockAccessCode;
use App\Jobs\DeleteTTLockAccessCode;
use App\Jobs\SendBookingCancelled;
use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingLocker;
use App\Models\Locker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private PricingService $pricingService,
    ) {}

    public function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            // Normalise legacy (locker_size + locker_qty) → items[]. Multi-size bookings
            // come in as $data['items'] = [{size, qty, duration?}, ...]. Each item may
            // optionally specify its own duration; otherwise the global $data['duration']
            // applies to all items (legacy single-window mode).
            $items = $data['items'] ?? [[
                'size' => $data['locker_size'] ?? 'standard',
                'qty' => (int) ($data['locker_qty'] ?? 1),
            ]];
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
                    ->active()
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
            ]);

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
                    $pin = $this->generatePin();
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

            SendBookingConfirmation::dispatch($booking->id)->afterCommit();
            foreach ($bookingLockerIds as $blId) {
                CreateTTLockAccessCode::dispatch($blId)->afterCommit();
            }

            return $booking;
        });
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

    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        $booking->update([
            'booking_status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        // Remove TTLock keyboard passwords for any lockers attached to this booking
        foreach (BookingLocker::where('booking_id', $booking->id)->pluck('id') as $blId) {
            DeleteTTLockAccessCode::dispatch($blId)->afterCommit();
        }

        SendBookingCancelled::dispatch($booking->id)->afterCommit();

        return $booking;
    }
}
