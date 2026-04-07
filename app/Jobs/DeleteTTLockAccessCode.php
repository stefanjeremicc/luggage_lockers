<?php

namespace App\Jobs;

use App\Models\BookingLocker;
use App\Services\Lock\LockServiceInterface;
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
        $bl = BookingLocker::with('locker')->findOrFail($this->bookingLockerId);

        if (!$bl->locker->ttlock_lock_id || !$bl->ttlock_keyboard_pwd_id) {
            return;
        }

        $lockService->deleteAccessCode(
            $bl->locker->ttlock_lock_id,
            $bl->ttlock_keyboard_pwd_id
        );
    }
}
