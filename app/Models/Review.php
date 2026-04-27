<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'name', 'email', 'text', 'rating', 'source', 'status', 'avatar_letter',
        'is_featured', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'rating' => 'integer',
        ];
    }
}
