<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TtlockApiLog extends Model
{
    public $timestamps = false;

    protected $table = 'ttlock_api_log';

    protected $fillable = [
        'endpoint', 'method', 'request_params', 'response_body',
        'response_code', 'errcode', 'duration_ms', 'related_type',
        'related_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'request_params' => 'array',
            'response_body' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
