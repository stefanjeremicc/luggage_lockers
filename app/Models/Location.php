<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name', 'name_sr', 'slug', 'address', 'address_sr', 'city', 'city_sr', 'lat', 'lng',
        'description', 'description_sr', 'opening_time', 'closing_time',
        'is_24h', 'timezone', 'phone', 'whatsapp', 'email',
        'google_maps_url', 'meta_title', 'meta_title_sr',
        'meta_description', 'meta_description_sr', 'og_image', 'image_url',
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

    public function nameFor(string $locale): string
    {
        return $locale === 'sr' && $this->name_sr ? $this->name_sr : (string) $this->name;
    }

    public function addressFor(string $locale): string
    {
        return $locale === 'sr' && $this->address_sr ? $this->address_sr : (string) $this->address;
    }

    public function cityFor(string $locale): string
    {
        return $locale === 'sr' && $this->city_sr ? $this->city_sr : (string) $this->city;
    }

    public function descriptionFor(string $locale): ?string
    {
        return $locale === 'sr' && $this->description_sr ? $this->description_sr : $this->description;
    }

    public function metaTitleFor(string $locale): ?string
    {
        return $locale === 'sr' && $this->meta_title_sr ? $this->meta_title_sr : $this->meta_title;
    }

    public function metaDescriptionFor(string $locale): ?string
    {
        return $locale === 'sr' && $this->meta_description_sr ? $this->meta_description_sr : $this->meta_description;
    }
}
