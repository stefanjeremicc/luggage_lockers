<?php

namespace App\Http\Controllers\Public;

use App\Enums\LockerSize;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\PricingRule;

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

        // Sample locker per size for dimensions
        $sampleLockers = $location->lockers()
            ->active()
            ->where('is_published_on_site', true)
            ->get()
            ->groupBy(fn ($l) => $l->size->value)
            ->map(fn ($group) => $group->first());

        // Lowest "from" price per size — prefer location-specific, fall back to global
        $sizeSummary = collect(['standard', 'large'])
            ->mapWithKeys(function ($sizeKey) use ($location, $sizes, $sampleLockers) {
                if (!$sizes->has($sizeKey)) return [$sizeKey => null];
                $sizeEnum = LockerSize::from($sizeKey);
                $minPrice = PricingRule::active()
                    ->where('locker_size', $sizeEnum)
                    ->where(fn ($q) => $q->where('location_id', $location->id)->orWhereNull('location_id'))
                    ->orderBy('price_eur')
                    ->value('price_eur');

                $sample = $sampleLockers->get($sizeKey);
                return [$sizeKey => (object) [
                    'count' => $sizes->get($sizeKey),
                    'from_price' => $minPrice ? (int) $minPrice : null,
                    'dimensions' => $sample?->dimensions_cm,
                    'image' => "/images/lockers/{$sizeKey}.webp",
                ]];
            });

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

        return view('public.locations.show', compact('location', 'sizes', 'sizeSummary', 'nearbyLocations'));
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
