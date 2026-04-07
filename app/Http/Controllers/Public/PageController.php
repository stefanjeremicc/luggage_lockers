<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Page;
use App\Models\PricingRule;

class PageController extends Controller
{
    public function about()
    {
        $page = Page::published()->forLocale(app()->getLocale())->where('slug', 'about')->first();
        return view('public.pages.about', compact('page'));
    }

    public function pricing()
    {
        $pricingRules = PricingRule::active()
            ->global()
            ->orderBy('locker_size')
            ->orderBy('price_eur')
            ->get()
            ->groupBy(fn ($rule) => $rule->locker_size->value);

        return view('public.pages.pricing', compact('pricingRules'));
    }

    public function contact()
    {
        $locations = Location::active()->orderBy('sort_order')->get();

        return view('public.pages.contact', compact('locations'));
    }

    public function terms()
    {
        $page = Page::published()->forLocale(app()->getLocale())->where('slug', 'terms')->first();
        return view('public.pages.terms', compact('page'));
    }

    public function privacy()
    {
        $page = Page::published()->forLocale(app()->getLocale())->where('slug', 'privacy')->first();
        return view('public.pages.privacy', compact('page'));
    }
}
