<?php

namespace App\Models;

use App\Enums\LockerSize;
use App\Enums\LockerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Locker extends Model
{
    protected $fillable = [
        'location_id', 'ttlock_lock_id', 'uuid', 'number', 'size', 'status',
        'battery_level', 'is_online', 'dimensions_cm', 'sort_order',
        'is_active', 'last_synced_at', 'last_used_at', 'is_published_on_site', 'site_sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size' => LockerSize::class,
            'status' => LockerStatus::class,
            'is_online' => 'boolean',
            'is_active' => 'boolean',
            'is_published_on_site' => 'boolean',
            'dimensions_cm' => 'array',
            'last_synced_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_lockers')
            ->withPivot('pin_code_encrypted', 'ttlock_keyboard_pwd_id', 'assigned_at');
    }

    public function currentBookings(): BelongsToMany
    {
        return $this->bookings()
            ->whereIn('booking_status', [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Active])
            ->where('check_in', '<=', now())
            ->where('check_out', '>=', now());
    }

    public function upcomingBookings(): BelongsToMany
    {
        return $this->bookings()
            ->whereIn('booking_status', [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Active])
            ->where('check_in', '>', now());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', LockerStatus::Available);
    }

    /**
     * Bookable = admin-enabled (is_active) AND physically reachable via TTLock
     * gateway (is_online). Used by availability/booking flows so customers
     * never reserve a locker we can't actually open for them.
     */
    public function scopeBookable($query)
    {
        return $query->where('is_active', true)->where('is_online', true);
    }
}
