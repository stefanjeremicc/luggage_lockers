<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Location;
use App\Models\PricingRule;
use App\Models\Review;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();

        $locations = Location::active()->orderBy('sort_order')->get();
        $faqs = Faq::active()->limit(6)->get()->map(fn ($f) => (object) [
            'question' => $f->questionFor($locale),
            'answer' => $f->answerFor($locale),
        ]);
        $blogPosts = BlogPost::published()->limit(3)->get()->map(fn ($p) => (object) [
            'slug' => $p->slug,
            'title' => $p->titleFor($locale),
            'excerpt' => $p->excerptFor($locale),
            'featured_image' => $p->featured_image,
            'published_at' => $p->published_at,
        ]);

        // Pricing from DB
        $pricingRules = PricingRule::active()->global()->orderBy('price_eur')->get()->groupBy(fn($r) => $r->locker_size->value);

        // Reviews from DB — only approved/legacy ones (status='approved' or null for old data)
        $reviews = Review::where('is_active', true)
            ->where(function ($q) {
                $q->where('status', 'approved')->orWhereNull('status');
            })
            ->orderBy('sort_order')
            ->limit(9)
            ->get();

        // How It Works steps — authored in config/homepage.php
        $howItWorksSteps = config('homepage.how_it_works', []);

        return view('public.home', compact('locations', 'faqs', 'blogPosts', 'pricingRules', 'reviews', 'howItWorksSteps'));
    }
}
