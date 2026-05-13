<?php

namespace App\Jobs;

use App\Models\BookingLocker;
use App\Models\TtlockGateway;
use App\Services\Booking\BookingService;
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

    /**
     * How many minutes to pad on each side of the booking window when we
     * register the passcode on TTLock cloud. The customer expects the keypad
     * to accept the PIN a little before check-in and a little after check-out;
     * the cleanup job (DeleteExpiredBookingPins) waits the same amount past
     * check_out before deleting from TTLock, keeping the two ends symmetric.
     */
    public const TTLOCK_BUFFER_MINUTES = 30;

    public function __construct(
        private int $bookingLockerId,
    ) {}

    public function handle(LockServiceInterface $lockService, BookingService $bookingService): void
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

        // Register the PIN in TTLock cloud regardless of gateway state. The cloud
        // accepts the passcode immediately (visible in the TTLock mobile app and
        // returned via API), and if a gateway is online it pushes to the physical
        // lock within ~30s. Without a gateway the PIN syncs the next time the app
        // is near the lock over Bluetooth — better than blocking the booking flow
        // waiting for a gateway that may not exist for this site.
        if (!TtlockGateway::where('is_online', true)->exists()) {
            Log::info('TTLock passcode created without active gateway — will sync via BLE on next app visit', [
                'booking_locker_id' => $bl->id,
            ]);
        }

        // TTLock cloud's passcode namespace is per-account, not per-booking. A PIN we
        // generated locally may collide with one already registered by another lock
        // under the same TTLock account (error -3007). On collision we regenerate the
        // PIN inline a few times before giving up — Laravel-level retries would just
        // replay the same encrypted PIN and fail identically.
        // Apply the ±buffer here, not on $booking. The booking row keeps the
        // exact times the customer agreed to (and what they see in emails);
        // TTLock just gets the relaxed window so the keypad is forgiving by
        // ±30 min on each end. We .copy() before subMinutes/addMinutes so the
        // model's own Carbon instances stay unmodified.
        $ttlockStart = $bl->booking->check_in->copy()->subMinutes(self::TTLOCK_BUFFER_MINUTES);
        $ttlockEnd   = $bl->booking->check_out->copy()->addMinutes(self::TTLOCK_BUFFER_MINUTES);

        $maxInlineRetries = 5;
        $response = null;
        for ($attempt = 1; $attempt <= $maxInlineRetries; $attempt++) {
            $pin = Crypt::decryptString($bl->pin_code_encrypted);
            try {
                $response = $lockService->createTimedAccessCode(
                    $bl->locker->ttlock_lock_id,
                    $pin,
                    $ttlockStart,
                    $ttlockEnd
                );
                break;
            } catch (\RuntimeException $e) {
                if (!str_contains($e->getMessage(), '-3007')) {
                    throw $e;
                }
                Log::warning('TTLock passcode collision, regenerating', [
                    'booking_locker_id' => $bl->id,
                    'inline_attempt' => $attempt,
                ]);
                $bl->update([
                    'pin_code_encrypted' => Crypt::encryptString($bookingService->generatePin()),
                ]);
                $bl->refresh();
                if ($attempt === $maxInlineRetries) {
                    throw $e;
                }
            }
        }

        $bl->update([
            'ttlock_keyboard_pwd_id' => $response['keyboardPwdId'] ?? null,
        ]);

        // After TTLock cloud has accepted the password, push the PIN to the customer.
        BookingNotifier::sendPinDelivered($bl->fresh(['booking.customer', 'booking.location', 'locker']));
    }
}
