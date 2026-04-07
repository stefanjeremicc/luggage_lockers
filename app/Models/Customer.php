<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'uuid', 'full_name', 'email', 'phone', 'country_code',
        'oauth_provider', 'oauth_id', 'locale', 'whatsapp_opt_in',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_opt_in' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
