<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Flip Confirmed/Active bookings to Expired once check_out has passed.
 *
 * We deliberately do NOT delete the TTLock passcode here or send any
 * "expired" notification — both responsibilities moved to
 * DeleteExpiredBookingPins (runs at check_out + 30 min so customers still
 * have the 30 min grace window we baked into the TTLock window) and to the
 * review-request flow.
 */
class HandleExpiredBookings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Booking::whereIn('booking_status', [BookingStatus::Confirmed, BookingStatus::Active])
            ->where('check_out', '<', now())
            ->update(['booking_status' => BookingStatus::Expired]);
    }
}
