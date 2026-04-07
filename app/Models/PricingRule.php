<?php

namespace App\Models;

use App\Enums\LockerSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    protected $fillable = [
        'location_id', 'locker_size', 'duration_key', 'price_eur', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'locker_size' => LockerSize::class,
            'price_eur' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('location_id');
    }

    public function getLabelAttribute(): string
    {
        return match ($this->duration_key) {
            '6h' => 'Up to 6 hours',
            '24h' => '24 hours',
            '2_days' => '2 days',
            '3_days' => '3 days',
            '4_days' => '4 days',
            '5_days' => '5 days',
            '1_week' => '1 week',
            '2_weeks' => '2 weeks',
            '1_month' => '1 month',
            default => $this->duration_key,
        };
    }
}
