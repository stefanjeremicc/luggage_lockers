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
            ->withCount(['lockers' => fn($q) => $q->active()])
            ->firstOrFail();

        return view('public.locations.show', compact('location'));
    }
}
