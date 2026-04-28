<?php

namespace App\Models;

use App\Enums\LockerSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingItem extends Model
{
    protected $fillable = [
        'booking_id', 'locker_size', 'qty', 'duration_key',
        'check_in', 'check_out', 'unit_price_eur', 'line_total_eur',
    ];

    protected function casts(): array
    {
        return [
            'locker_size' => LockerSize::class,
            'qty' => 'integer',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'unit_price_eur' => 'decimal:2',
            'line_total_eur' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingLockers(): HasMany
    {
        return $this->hasMany(BookingLocker::class);
    }
}
