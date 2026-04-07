<?php

namespace App\Jobs;

use App\Models\BookingLocker;
use App\Services\Lock\LockServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateTTLockAccessCode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [30, 60, 120, 300, 600];

    public function __construct(
        private int $bookingLockerId,
    ) {}

    public function handle(LockServiceInterface $lockService): void
    {
        $bl = BookingLocker::with(['booking', 'locker'])->findOrFail($this->bookingLockerId);

        if (!$bl->locker->ttlock_lock_id) {
            return;
        }

        $pin = decrypt($bl->pin_code_encrypted);

        $response = $lockService->createTimedAccessCode(
            $bl->locker->ttlock_lock_id,
            $pin,
            $bl->booking->check_in,
            $bl->booking->check_out
        );

        $bl->update([
            'ttlock_keyboard_pwd_id' => $response['keyboardPwdId'] ?? null,
        ]);
    }
}
