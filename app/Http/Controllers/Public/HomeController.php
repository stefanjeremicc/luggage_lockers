<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Location;
use App\Models\Page;
use App\Models\PricingRule;
use App\Models\Review;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        return view('public.home', $this->homeData());
    }

    /**
     * SEO landing page — renders the FULL homepage layout but with a
     * page-specific H1, subtitle, hero image/alt and meta tags, pulled from
     * config/landing_pages.php. Clean root-level URL (e.g. /luggage-storage-belgrade).
     */
    public function landing(string $slug)
    {
        $all = config('landing_pages', []);
        $locale = app()->getLocale();
        $sr = $locale === 'sr';

        // Resolve the config entry + its canonical (EN) key. In EN the URL slug
        // IS the config key; in SR the URL is the entry's slug_sr.
        $key = null;
        $cfg = null;
        if ($sr) {
            foreach ($all as $k => $c) {
                if (($c['slug_sr'] ?? null) === $slug) {
                    $key = $k;
                    $cfg = $c;
                    break;
                }
            }
        } elseif (isset($all[$slug])) {
            $key = $slug;
            $cfg = $all[$slug];
        }
        abort_unless(is_array($cfg), 404);

        $pick = fn (string $k) => $sr ? ($cfg[$k . '_sr'] ?? $cfg[$k] ?? null) : ($cfg[$k] ?? null);

        $landing = (object) [
            'slug'             => $key,                     // EN slug (config key / EN URL)
            'slug_sr'          => $cfg['slug_sr'] ?? null,  // SR URL slug (for hreflang)
            'h1'               => $pick('h1'),
            'subtitle'         => $pick('subtitle'),
            'meta_title'       => $pick('meta_title'),
            'meta_description' => $pick('meta_description'),
            'hero_image'       => $cfg['hero_image'] ?? null,
            'hero_alt'         => $pick('hero_alt'),
            'og_image'         => $cfg['hero_image'] ?? null,
            // Unique body content so these are real pages, not homepage clones.
            'intro'            => $pick('intro'),
            'faqs'             => $sr ? ($cfg['faqs_sr'] ?? $cfg['faqs'] ?? []) : ($cfg['faqs'] ?? []),
        ];

        return view('public.home', $this->homeData() + ['landing' => $landing]);
    }

    /** Shared homepage view data (used by the homepage and SEO landing pages). */
    private function homeData(): array
    {
        $locale = app()->getLocale();

        $locations = Location::active()->orderBy('sort_order')->get();
        $faqs = Faq::active()->limit(6)->get()->map(fn ($f) => (object) [
            'question' => $f->questionFor($locale),
            'answer' => $f->answerFor($locale),
        ]);
        $blogPosts = BlogPost::published()
            ->where('is_featured', true)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get()
            ->map(fn ($p) => (object) [
            'slug' => $p->slugFor($locale),
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

        // Homepage CMS sections — DB-authored, with config fallback
        $home = Page::seoFor('home', $locale);
        $configSteps = config('homepage.how_it_works', []);
        $sectionSteps = $home?->section('how_it_works');
        $howItWorksSteps = is_array($sectionSteps) && count($sectionSteps) === 4 && !empty($sectionSteps[0]['title'])
            ? $sectionSteps
            : $configSteps;

        return compact('locations', 'faqs', 'blogPosts', 'pricingRules', 'reviews', 'howItWorksSteps', 'home');
    }
}
