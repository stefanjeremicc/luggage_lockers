<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPost extends Model
{
    protected $fillable = [
        'slug', 'slug_sr', 'title', 'title_sr', 'excerpt', 'excerpt_sr',
        'content', 'content_sr', 'featured_image',
        'blog_category_id',
        'tags', 'meta_title', 'meta_title_sr',
        'meta_description', 'meta_description_sr',
        'author_name', 'is_published', 'is_featured', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderByDesc('published_at');
    }

    public function titleFor(string $locale): string
    {
        return $locale === 'sr' && $this->title_sr ? $this->title_sr : (string) $this->title;
    }

    public function excerptFor(string $locale): string
    {
        return $locale === 'sr' && $this->excerpt_sr ? $this->excerpt_sr : (string) $this->excerpt;
    }

    public function contentFor(string $locale): string
    {
        return $locale === 'sr' && $this->content_sr ? $this->content_sr : (string) $this->content;
    }

    public function metaTitleFor(string $locale): ?string
    {
        return $locale === 'sr' && $this->meta_title_sr ? $this->meta_title_sr : $this->meta_title;
    }

    public function metaDescriptionFor(string $locale): ?string
    {
        return $locale === 'sr' && $this->meta_description_sr ? $this->meta_description_sr : $this->meta_description;
    }

    public function slugFor(string $locale): string
    {
        return $locale === 'sr' && $this->slug_sr ? $this->slug_sr : (string) $this->slug;
    }
}
