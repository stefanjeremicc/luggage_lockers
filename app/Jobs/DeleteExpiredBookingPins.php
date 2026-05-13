<?php

namespace App\Jobs;

use App\Models\BookingLocker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduler hook (every minute): find booking_locker rows whose booking has
 * fully expired (check_out + TTLOCK_BUFFER_MINUTES <= now) and that still
 * carry a TTLock-side `keyboardPwdId`, and clean them out of TTLock cloud.
 *
 * Why the buffer: the passcode was registered with TTLock for the booking
 * window padded by ±30 min on each side, so the customer can be a little late
 * collecting their bag. We only delete once that buffered tail has elapsed.
 *
 * Permanent codes (admin-issued, no booking_locker row referencing them) are
 * untouched — we identify deletable codes strictly by our local
 * `booking_lockers.ttlock_keyboard_pwd_id`.
 */
class DeleteExpiredBookingPins implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $cutoff = now()->subMinutes(CreateTTLockAccessCode::TTLOCK_BUFFER_MINUTES);

        $rows = BookingLocker::query()
            ->whereNotNull('ttlock_keyboard_pwd_id')
            ->whereHas('booking', fn ($q) => $q->where('check_out', '<=', $cutoff))
            ->limit(50)
            ->get(['id', 'ttlock_keyboard_pwd_id']);

        if ($rows->isEmpty()) {
            return;
        }

        Log::info('Cleaning up expired TTLock passcodes', ['count' => $rows->count()]);

        foreach ($rows as $bl) {
            // Hand each one off to the existing DeleteTTLockAccessCode job which
            // already knows how to handle retries + clear our local pointer.
            DeleteTTLockAccessCode::dispatch($bl->id);
        }
    }
}
