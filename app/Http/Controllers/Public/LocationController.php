<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Location;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::active()
            ->withCount(['lockers' => fn($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();

        return view('public.locations.index', compact('locations'));
    }

    public function show(string $slug)
    {
        $location = Location::where('slug', $slug)
            ->active()
            ->withCount([
                'lockers' => fn($q) => $q->active()->where('is_published_on_site', true),
                'lockers as available_count' => fn($q) => $q->active()->where('is_published_on_site', true)->where('status', 'available'),
            ])
            ->firstOrFail();

        $sizes = $location->lockers()
            ->active()
            ->where('is_published_on_site', true)
            ->selectRaw('size, COUNT(*) as count')
            ->groupBy('size')
            ->pluck('count', 'size');

        $nearbyLocations = Location::active()
            ->where('id', '!=', $location->id)
            ->withCount(['lockers' => fn($q) => $q->active()->where('is_published_on_site', true)])
            ->get()
            ->map(function ($l) use ($location) {
                $l->distance_km = $this->haversineKm(
                    (float) $location->lat, (float) $location->lng,
                    (float) $l->lat, (float) $l->lng
                );
                return $l;
            })
            ->sortBy('distance_km')
            ->take(3)
            ->values();

        return view('public.locations.show', compact('location', 'sizes', 'nearbyLocations'));
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
