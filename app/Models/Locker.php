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
        'is_active', 'last_synced_at', 'is_published_on_site', 'site_sort_order',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', LockerStatus::Available);
    }
}
