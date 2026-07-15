<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class BookingLocker extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id', 'booking_item_id', 'locker_id', 'pin_code_encrypted',
        'ttlock_keyboard_pwd_id', 'assigned_at', 'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(Locker::class);
    }

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    /**
     * Attribute a TTLock unlock to the booking whose CUSTOMER PIN was used, and
     * stamp its opened_at. Only passcode unlocks whose keyboardPwd matches a
     * booking's own PIN count as "the customer opened it" — admin remote-unlock,
     * app/gateway unlock, and master/staff codes carry no matching customer
     * passcode and are correctly ignored (that was the source of false flags).
     *
     * Idempotent: records only the FIRST customer open (whereNull opened_at), so
     * re-processing the same record (webhook + sync overlap) is a no-op.
     *
     * @param  int          $ttlockLockId  TTLock's numeric lock id.
     * @param  string|null  $passcode      The passcode used (record's keyboardPwd).
     * @param  Carbon       $at            When the unlock happened.
     */
    public static function recordCustomerUnlock(int $ttlockLockId, ?string $passcode, Carbon $at): void
    {
        $passcode = trim((string) ($passcode ?? ''));
        if ($passcode === '' || $ttlockLockId <= 0) {
            return; // non-passcode unlock (admin/remote/app) → not a customer open
        }

        $locker = Locker::where('ttlock_lock_id', $ttlockLockId)->first();
        if (!$locker) {
            return;
        }

        // Only booking-lockers on THIS lock, not yet stamped, whose (non-cancelled)
        // booking had started by the unlock time. The passcode is unique per
        // booking and is deleted after checkout, so a match is unambiguous.
        $candidates = self::with('booking:id,check_in,booking_status')
            ->where('locker_id', $locker->id)
            ->whereNull('opened_at')
            ->whereHas('booking', fn ($q) => $q
                ->where('booking_status', '!=', BookingStatus::Cancelled)
                ->where('check_in', '<=', $at))
            ->get();

        foreach ($candidates as $bl) {
            try {
                $pin = Crypt::decryptString($bl->pin_code_encrypted);
            } catch (\Throwable) {
                continue;
            }
            if (trim($pin) === $passcode) {
                $bl->opened_at = $at;
                $bl->save();
                return;
            }
        }
    }
}
