<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TtlockGateway extends Model
{
    protected $fillable = [
        'ttlock_gateway_id', 'name', 'lock_count', 'is_online',
        'last_seen_at', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }
}
