<?php

namespace App\Jobs;

use App\Models\BookingLocker;
use App\Models\TtlockGateway;
use App\Services\Lock\LockServiceInterface;
use App\Services\Notification\BookingNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        $bl = BookingLocker::with(['booking.customer', 'booking.location', 'locker'])->findOrFail($this->bookingLockerId);

        // No physical lock mapped → still notify the customer with the PIN so manual override works.
        if (!$bl->locker->ttlock_lock_id) {
            Log::warning('TTLock skipped: locker has no ttlock_lock_id', [
                'booking_locker_id' => $bl->id,
                'locker_id' => $bl->locker_id,
            ]);
            BookingNotifier::sendPinDelivered($bl);
            return;
        }

        // Pre-flight: if no gateway is online, the passcode will land in TTLock cloud
        // but never reach the physical lock. Refuse to create — let job retry/backoff.
        $gatewayOnline = TtlockGateway::where('is_online', true)->exists();
        if (!$gatewayOnline) {
            Log::warning('TTLock passcode creation deferred: no gateway online', [
                'booking_locker_id' => $bl->id,
                'attempt' => $this->attempts(),
            ]);
            $this->release(60);
            return;
        }

        $pin = Crypt::decryptString($bl->pin_code_encrypted);

        $response = $lockService->createTimedAccessCode(
            $bl->locker->ttlock_lock_id,
            $pin,
            $bl->booking->check_in,
            $bl->booking->check_out
        );

        $bl->update([
            'ttlock_keyboard_pwd_id' => $response['keyboardPwdId'] ?? null,
        ]);

        // After TTLock cloud has accepted the password, push the PIN to the customer.
        BookingNotifier::sendPinDelivered($bl->fresh(['booking.customer', 'booking.location', 'locker']));
    }
}
