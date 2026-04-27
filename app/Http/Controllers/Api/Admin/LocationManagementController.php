<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationManagementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Location::orderBy('sort_order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:locations,slug',
            'address' => 'required|string|max:500',
            'city' => 'string|max:100',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'description' => 'nullable|string',
            'description_sr' => 'nullable|string',
            'is_24h' => 'boolean',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'google_maps_url' => 'nullable|url|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'image_url' => 'nullable|string|max:500',
        ]);

        $location = Location::create($validated);
        return response()->json($location, 201);
    }

    public function show($id): JsonResponse
    {
        $query = Location::withCount('lockers');
        $location = is_numeric($id)
            ? $query->findOrFail((int) $id)
            : $query->where('slug', $id)->firstOrFail();
        return response()->json($location);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $location = Location::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:locations,slug,' . $id,
            'address' => 'sometimes|string|max:500',
            'city' => 'nullable|string|max:100',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
            'description' => 'nullable|string',
            'description_sr' => 'nullable|string',
            'is_24h' => 'boolean',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'google_maps_url' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'image_url' => 'nullable|string|max:500',
        ]);
        $location->update($validated);
        return response()->json($location);
    }

    public function destroy(int $id): JsonResponse
    {
        Location::findOrFail($id)->update(['is_active' => false]);
        return response()->json(['message' => 'Location deactivated']);
    }
}
