<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendBookingCancelled;
use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Models\BookingLocker;
use App\Models\Locker;
use Carbon\Carbon;
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
            $checkIn = Carbon::parse("{$data['date']} {$data['time']}");
            $checkOut = $this->availabilityService->calculateCheckOut($checkIn, $data['duration']);
            $pricing = $this->pricingService->calculate(
                $data['location_id'],
                $data['locker_size'],
                $data['duration'],
                $data['locker_qty']
            );

            if (isset($pricing['error'])) {
                throw new \RuntimeException($pricing['error']);
            }

            // Lock and check availability
            $availableLockers = Locker::where('location_id', $data['location_id'])
                ->where('size', $data['locker_size'])
                ->active()
                ->lockForUpdate()
                ->get();

            $availability = $this->availabilityService->check(
                $data['location_id'],
                $data['date'],
                $data['time'],
                $data['duration']
            );

            $availableCount = $availability[$data['locker_size']]['available'];

            if ($availableCount < $data['locker_qty']) {
                throw new \App\Exceptions\NotAvailableException(
                    'Sorry, the selected lockers are no longer available.',
                    $availability
                );
            }

            // Create booking
            $booking = Booking::create([
                'uuid' => Str::uuid(),
                'customer_id' => $data['customer_id'],
                'location_id' => $data['location_id'],
                'locker_size' => $data['locker_size'],
                'locker_qty' => $data['locker_qty'],
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

            // Assign lockers (no PIN in phase 1 — client manages PINs via TTLock app)
            $bookedLockerIds = $this->getBookedLockerIds($data['location_id'], $data['locker_size'], $checkIn, $checkOut);
            $freeLockers = $availableLockers->reject(fn($l) => in_array($l->id, $bookedLockerIds))->take($data['locker_qty']);

            foreach ($freeLockers as $locker) {
                BookingLocker::create([
                    'booking_id' => $booking->id,
                    'locker_id' => $locker->id,
                    'assigned_at' => now(),
                ]);
            }

            $booking->load('lockers', 'location', 'customer');

            SendBookingConfirmation::dispatch($booking->id)->afterCommit();

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

    public function generateUniquePin(int $lockerId): string
    {
        do {
            $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $exists = BookingLocker::where('locker_id', $lockerId)
                ->whereHas('booking', fn($q) => $q->active())
                ->where('pin_code_encrypted', encrypt($pin))
                ->exists();
        } while ($exists);

        return $pin;
    }

    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        $booking->update([
            'booking_status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        SendBookingCancelled::dispatch($booking->id)->afterCommit();

        return $booking;
    }
}
