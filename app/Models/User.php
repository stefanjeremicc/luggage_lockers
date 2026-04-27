<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'location_ids', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'location_ids' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function hasAccessToLocation(int $locationId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($locationId, $this->location_ids ?? []);
    }
}
