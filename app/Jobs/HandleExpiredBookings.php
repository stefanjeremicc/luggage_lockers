<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleExpiredBookings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expired = Booking::whereIn('booking_status', [BookingStatus::Confirmed, BookingStatus::Active])
            ->where('check_out', '<', now())
            ->get();

        foreach ($expired as $booking) {
            $booking->update(['booking_status' => BookingStatus::Expired]);

            // Delete TTLock access codes
            foreach ($booking->lockers as $locker) {
                if ($locker->pivot->ttlock_keyboard_pwd_id) {
                    DeleteTTLockAccessCode::dispatch($locker->pivot->id);
                }
            }

            SendExpiredNotification::dispatch($booking->id);
        }
    }
}
