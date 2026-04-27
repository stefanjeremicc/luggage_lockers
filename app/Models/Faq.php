<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    protected $fillable = [
        'question', 'question_sr', 'answer', 'answer_sr',
        'faq_category_id', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function questionFor(string $locale): string
    {
        return $locale === 'sr' && $this->question_sr ? $this->question_sr : (string) $this->question;
    }

    public function answerFor(string $locale): string
    {
        return $locale === 'sr' && $this->answer_sr ? $this->answer_sr : (string) $this->answer;
    }
}
