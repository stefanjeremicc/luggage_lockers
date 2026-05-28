<?php

namespace App\Jobs;

use App\Models\BookingLocker;
use App\Services\Lock\LockServiceInterface;
use App\Services\Lock\TtlockQuota;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteTTLockAccessCode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private int $bookingLockerId,
    ) {}

    public function handle(LockServiceInterface $lockService): void
    {
        // Quota exhausted → the delete call can't succeed and would just throw
        // a TtlockQuotaExceededException (logged with a full stacktrace) on
        // every retry. Return quietly; the local ttlock_keyboard_pwd_id is left
        // intact so DeleteExpiredBookingPins re-queues this cleanup once the
        // quota resets next month.
        if (TtlockQuota::isExceeded()) {
            return;
        }

        // The booking_locker row may already be gone (admin force-delete, or
        // the booking was wiped before this job ran). Silently swallow that —
        // the only thing this job does is best-effort cleanup of a TTLock
        // passcode we created. No row means no passcode to delete on our side.
        $bl = BookingLocker::with('locker')->find($this->bookingLockerId);
        if (!$bl) {
            return;
        }

        if (!$bl->locker?->ttlock_lock_id || !$bl->ttlock_keyboard_pwd_id) {
            return;
        }

        $lockService->deleteAccessCode(
            $bl->locker->ttlock_lock_id,
            $bl->ttlock_keyboard_pwd_id
        );

        // Clear our local pointer so the cleanup scheduler doesn't re-queue
        // this row every tick once TTLock has accepted the delete.
        $bl->update(['ttlock_keyboard_pwd_id' => null]);
    }
}
