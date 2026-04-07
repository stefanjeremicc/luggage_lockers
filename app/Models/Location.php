<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name', 'slug', 'address', 'city', 'lat', 'lng',
        'description', 'description_sr', 'opening_time', 'closing_time',
        'is_24h', 'timezone', 'phone', 'whatsapp', 'email',
        'google_maps_url', 'meta_title', 'meta_description', 'og_image',
        'settings', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:8',
            'lng' => 'decimal:8',
            'is_24h' => 'boolean',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function lockers(): HasMany
    {
        return $this->hasMany(Locker::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
