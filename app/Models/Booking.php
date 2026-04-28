<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\LockerSize;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'uuid', 'customer_id', 'location_id', 'locker_size', 'locker_qty',
        'check_in', 'check_out', 'duration_label', 'price_eur', 'price_rsd',
        'service_fee_eur', 'total_eur', 'booking_status', 'payment_status',
        'payment_method', 'paid_at', 'cancelled_at', 'cancel_reason',
        'notes', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'locker_size' => LockerSize::class,
            'booking_status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'price_eur' => 'decimal:2',
            'price_rsd' => 'decimal:2',
            'service_fee_eur' => 'decimal:2',
            'total_eur' => 'decimal:2',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function lockers(): BelongsToMany
    {
        return $this->belongsToMany(Locker::class, 'booking_lockers')
            ->withPivot('pin_code_encrypted', 'ttlock_keyboard_pwd_id', 'assigned_at', 'booking_item_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeActive($query)
    {
        return $query->whereIn('booking_status', [
            BookingStatus::Confirmed,
            BookingStatus::Active,
        ]);
    }
}
