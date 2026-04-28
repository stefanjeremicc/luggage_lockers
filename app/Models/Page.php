<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug', 'locale', 'type', 'title', 'content', 'sections', 'location_id',
        'meta_title', 'meta_description', 'og_image', 'canonical_url',
        'og_title', 'og_description', 'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sections' => 'array',
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLandings($query)
    {
        return $query->where('type', 'landing');
    }

    /**
     * Read a section value from `sections` JSON with safe fallback.
     * Example: $page->section('hero.title', 'Default').
     */
    public function section(string $path, mixed $default = null): mixed
    {
        return data_get($this->sections ?? [], $path, $default);
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
