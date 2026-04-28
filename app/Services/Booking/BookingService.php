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
            // come in as $data['items'] = [{size, qty}, ...].
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

            // User enters local (Belgrade) date+time; parse in display_timezone, then convert
            // to UTC before storing — Eloquent serializes the Carbon's wall-clock string
            // without applying any timezone conversion, so the conversion must be explicit.
            $checkIn = Carbon::parse("{$data['date']} {$data['time']}", config('app.display_timezone'))
                ->setTimezone('UTC');
            $checkOut = $this->availabilityService->calculateCheckOut($checkIn, $data['duration']);

            $pricing = $this->pricingService->calculateForItems(
                $data['location_id'],
                $items,
                $data['duration']
            );
            if (isset($pricing['error'])) {
                throw new \RuntimeException($pricing['error']);
            }

            // Per-size availability check + locker selection — must hold a row lock on the
            // locker rows we intend to take, so concurrent bookings can't race us.
            $availability = $this->availabilityService->check(
                $data['location_id'],
                $data['date'],
                $data['time'],
                $data['duration']
            );

            $totalQty = 0;
            $primarySize = null;
            $primaryQty = 0;
            $perSizeFreeLockers = [];

            foreach ($items as $item) {
                $size = $item['size'];
                $qty = (int) $item['qty'];
                $totalQty += $qty;

                $availableCount = $availability[$size]['available'] ?? 0;
                if ($availableCount < $qty) {
                    throw new \App\Exceptions\NotAvailableException(
                        "Sorry, only {$availableCount} {$size} locker(s) are available for this slot.",
                        $availability
                    );
                }

                $candidates = Locker::where('location_id', $data['location_id'])
                    ->where('size', $size)
                    ->active()
                    ->orderByRaw('last_used_at IS NULL DESC')
                    ->orderBy('last_used_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $bookedLockerIds = $this->getBookedLockerIds($data['location_id'], $size, $checkIn, $checkOut);
                $free = $candidates->reject(fn($l) => in_array($l->id, $bookedLockerIds))->take($qty);

                if ($free->count() < $qty) {
                    throw new \App\Exceptions\NotAvailableException(
                        "Sorry, the selected {$size} lockers are no longer available.",
                        $availability
                    );
                }

                $perSizeFreeLockers[$size] = ($perSizeFreeLockers[$size] ?? collect())->merge($free);

                if ($qty > $primaryQty) { $primaryQty = $qty; $primarySize = $size; }
            }

            // Booking row keeps a "primary" size for legacy display (size with the most
            // lockers). The full per-locker breakdown lives on the booking_lockers pivot
            // joined to lockers.size.
            $booking = Booking::create([
                'uuid' => Str::uuid(),
                'customer_id' => $data['customer_id'],
                'location_id' => $data['location_id'],
                'locker_size' => $primarySize ?: 'standard',
                'locker_qty' => $totalQty,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'duration_label' => $data['duration'],
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

            // Persist per-item rows (booking_items) and link booking_lockers to them.
            // For now all items share check_in/check_out/duration_key from the request;
            // schema is ready for per-item durations once the UI surfaces that.
            $bookingItemIds = [];
            $priceLines = collect($pricing['lines'] ?? [])->keyBy('size');

            $bookingLockerIds = [];
            foreach ($perSizeFreeLockers as $size => $lockers) {
                $line = $priceLines->get($size);
                $unit = (float) ($line['unit_price_eur'] ?? 0);
                $qty = $lockers->count();

                $item = BookingItem::create([
                    'booking_id' => $booking->id,
                    'locker_size' => $size,
                    'qty' => $qty,
                    'duration_key' => $data['duration'],
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'unit_price_eur' => $unit,
                    'line_total_eur' => $unit * $qty,
                ]);
                $bookingItemIds[$size] = $item->id;

                foreach ($lockers as $locker) {
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

    private function getBookedLockerIds(int $locationId, string $size, Carbon $checkIn, Carbon $checkOut): array
    {
        return BookingLocker::whereHas('booking', function ($q) use ($locationId, $size, $checkIn, $checkOut) {
            $q->where('location_id', $locationId)
                ->where('locker_size', $size)
                ->whereIn('booking_status', [BookingStatus::Confirmed, BookingStatus::Active])
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn);
        })->pluck('locker_id')->toArray();
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
