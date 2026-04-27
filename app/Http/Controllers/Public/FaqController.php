<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;

class FaqController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();

        $faqs = Faq::query()
            ->where('faqs.is_active', true)
            ->leftJoin('faq_categories', 'faqs.faq_category_id', '=', 'faq_categories.id')
            ->orderByRaw('COALESCE(faq_categories.sort_order, 999999)')
            ->orderBy('faq_categories.id')
            ->orderBy('faqs.sort_order')
            ->select('faqs.*')
            ->with('category')
            ->get()
            ->map(fn ($f) => (object) [
                'question' => $f->questionFor($locale),
                'answer' => $f->answerFor($locale),
                'category' => $locale === 'sr' && $f->category?->name_sr
                    ? $f->category->name_sr
                    : ($f->category?->name ?? ''),
            ]);

        $categories = $faqs->groupBy('category');

        return view('public.pages.faq', compact('faqs', 'categories'));
    }
}
