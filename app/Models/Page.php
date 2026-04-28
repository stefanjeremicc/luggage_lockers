<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug', 'locale', 'title', 'content', 'meta_title',
        'meta_description', 'og_image', 'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForLocale($query, string $locale = 'en')
    {
        return $query->where('locale', $locale);
    }

    /**
     * Look up SEO/content for a page slug, falling back to English when SR is missing.
     * Returns null when neither locale has a row.
     */
    public static function seoFor(string $slug, ?string $locale = null): ?self
    {
        $locale ??= app()->getLocale();
        return static::where('slug', $slug)
            ->where('locale', $locale)
            ->first()
            ?? static::where('slug', $slug)->where('locale', 'en')->first();
    }
}
