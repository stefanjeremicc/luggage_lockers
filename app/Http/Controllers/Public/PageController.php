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

    public function near(string $slug)
    {
        // Prefer admin-authored landing page when published in current locale.
        $locale = app()->getLocale();
        $landing = Page::landings()->published()
            ->where('slug', $slug)
            ->where(fn ($q) => $q->where('locale', $locale)->orWhere('locale', 'en'))
            ->orderByRaw("locale = '{$locale}' DESC")
            ->with('location')
            ->first();
        if ($landing) {
            $locations = Location::active()->orderBy('sort_order')->get();
            return view('public.pages.landing', compact('landing', 'locations'));
        }

        $pois = config('seo.near_pois', []);
        $poi = $pois[$slug] ?? null;
        abort_if(!$poi, 404);

        $locations = Location::active()->orderBy('sort_order')->get();

        if (!empty($poi['lat']) && !empty($poi['lng'])) {
            $locations = $locations->map(function ($l) use ($poi) {
                $l->distance_km = $this->haversineKm((float) $l->lat, (float) $l->lng, (float) $poi['lat'], (float) $poi['lng']);
                return $l;
            })->sortBy('distance_km')->values();
        }

        return view('public.pages.near', compact('poi', 'slug', 'locations'));
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
