<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationManagementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Location::orderBy('sort_order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $this->validateHours($validated);

        return response()->json(Location::create($validated), 201);
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
        $validated = $request->validate($this->rules($id, updating: true));
        $this->validateHours($validated);

        $location->update($validated);
        return response()->json($location);
    }

    public function destroy(int $id): JsonResponse
    {
        Location::findOrFail($id)->update(['is_active' => false]);
        return response()->json(['message' => 'Location deactivated']);
    }

    private function rules(?int $id = null, bool $updating = false): array
    {
        $required = $updating ? 'sometimes|required' : 'required';
        $requiredParts = $updating ? ['sometimes', 'required'] : ['required'];
        $slugRule = ['string', 'max:120', 'regex:/^[a-z0-9-]+$/'];
        $slugRule[] = $id
            ? Rule::unique('locations', 'slug')->ignore($id)
            : Rule::unique('locations', 'slug');

        return [
            'name' => "{$required}|string|max:255",
            'name_sr' => 'nullable|string|max:255',
            'slug' => array_merge($requiredParts, $slugRule),
            'slug_sr' => array_merge(['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/'], [
                $id ? Rule::unique('locations', 'slug_sr')->ignore($id) : Rule::unique('locations', 'slug_sr'),
            ]),
            'address' => "{$required}|string|max:500",
            'address_sr' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'city_sr' => 'nullable|string|max:100',
            'lat' => "{$required}|numeric|between:-90,90",
            'lng' => "{$required}|numeric|between:-180,180",
            'description' => 'nullable|string|max:10000',
            'description_sr' => 'nullable|string|max:10000',
            'is_24h' => 'boolean',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'google_maps_url' => 'nullable|url|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_title_sr' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_description_sr' => 'nullable|string|max:500',
            'image_url' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    private function validateHours(array $data): void
    {
        $is24h = $data['is_24h'] ?? false;
        if ($is24h) return;

        $open = $data['opening_time'] ?? null;
        $close = $data['closing_time'] ?? null;

        if ($open && $close && $open >= $close) {
            abort(422, 'Closing time must be after opening time.');
        }
    }
}
