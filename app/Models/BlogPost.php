<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = [
        'slug', 'locale', 'title', 'excerpt', 'content', 'featured_image',
        'category', 'tags', 'meta_title', 'meta_description', 'author_name',
        'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderByDesc('published_at');
    }

    public function scopeForLocale($query, string $locale = 'en')
    {
        return $query->where('locale', $locale);
    }
}
