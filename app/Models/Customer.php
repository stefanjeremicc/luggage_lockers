<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'uuid', 'full_name', 'email', 'phone', 'country_code',
        'oauth_provider', 'oauth_id', 'locale', 'whatsapp_opt_in',
    ];

    /**
     * Customer rows have no password column — all auth is via Sanctum tokens minted at
     * guest-registration. Hide password-related attributes from any accidental
     * serialisation that the parent class might trigger.
     */
    protected $hidden = ['remember_token'];

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
