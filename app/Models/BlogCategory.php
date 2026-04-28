<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    protected $fillable = ['name', 'name_sr', 'slug', 'sort_order'];

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function nameFor(string $locale): string
    {
        return $locale === 'sr' && $this->name_sr ? $this->name_sr : (string) $this->name;
    }
}
