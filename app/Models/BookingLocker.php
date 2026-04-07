<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingLocker extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id', 'locker_id', 'pin_code_encrypted',
        'ttlock_keyboard_pwd_id', 'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
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
}
