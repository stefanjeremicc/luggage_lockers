<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingLocker;
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

            // Delete TTLock access codes — query BookingLocker directly so we have the
            // real row id (Booking->lockers is a belongsToMany whose pivot doesn't expose
            // the booking_lockers.id column, which is what DeleteTTLockAccessCode needs).
            $bls = BookingLocker::where('booking_id', $booking->id)
                ->whereNotNull('ttlock_keyboard_pwd_id')
                ->get();
            foreach ($bls as $bl) {
                DeleteTTLockAccessCode::dispatch($bl->id);
            }

            SendExpiredNotification::dispatch($booking->id);
        }
    }
}
