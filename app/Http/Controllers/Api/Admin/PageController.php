<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(): JsonResponse
    {
        $pages = Page::orderBy('type')->orderBy('slug')->orderBy('locale')->get()
            ->groupBy('slug')
            ->map(function ($rows, $slug) {
                $first = $rows->first();
                return [
                    'slug' => $slug,
                    'type' => $first?->type ?? 'page',
                    'location_id' => $first?->location_id,
                    'en' => $rows->firstWhere('locale', 'en'),
                    'sr' => $rows->firstWhere('locale', 'sr'),
                ];
            })
            ->values();

        return response()->json($pages);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', 'unique:pages,slug'],
            'type' => 'required|in:page,landing',
            'location_id' => 'nullable|exists:locations,id',
            'title' => 'required|string|max:200',
        ]);

        foreach (['en', 'sr'] as $locale) {
            Page::create([
                'slug' => $data['slug'],
                'locale' => $locale,
                'type' => $data['type'],
                'location_id' => $data['location_id'] ?? null,
                'title' => $data['title'],
                'is_published' => false,
            ]);
        }

        return response()->json(['message' => 'Page created', 'slug' => $data['slug']], 201);
    }

    public function destroy(string $slug): JsonResponse
    {
        $deleted = Page::where('slug', $slug)->where('type', 'landing')->delete();
        if (!$deleted) {
            return response()->json(['message' => 'Only landing pages can be deleted'], 422);
        }
        return response()->json(['message' => 'Page deleted']);
    }

    public function show(string $slug): JsonResponse
    {
        $rows = Page::where('slug', $slug)->get()->keyBy('locale');
        $first = $rows->first();
        return response()->json([
            'slug' => $slug,
            'type' => $first?->type ?? 'page',
            'location_id' => $first?->location_id,
            'en' => $rows->get('en'),
            'sr' => $rows->get('sr'),
        ]);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        $rules = [
            'location_id' => 'nullable|exists:locations,id',
        ];
        foreach (['en', 'sr'] as $locale) {
            $rules += [
                "$locale.title" => 'nullable|string|max:200',
                "$locale.meta_title" => 'nullable|string|max:70',
                "$locale.meta_description" => 'nullable|string|max:300',
                "$locale.og_image" => 'nullable|string|max:500',
                "$locale.og_title" => 'nullable|string|max:200',
                "$locale.og_description" => 'nullable|string|max:500',
                "$locale.canonical_url" => 'nullable|string|max:500',
                "$locale.content" => 'nullable|string',
                "$locale.sections" => 'nullable|array',
                "$locale.is_published" => 'nullable|boolean',
            ];
        }
        $data = $request->validate($rules);

        foreach (['en', 'sr'] as $locale) {
            if (!isset($data[$locale])) continue;
            $payload = $data[$locale];
            $payload['is_published'] = (bool) ($payload['is_published'] ?? true);
            if ($payload['is_published'] && empty(Page::where('slug', $slug)->where('locale', $locale)->value('published_at'))) {
                $payload['published_at'] = now();
            }
            if (array_key_exists('location_id', $data)) {
                $payload['location_id'] = $data['location_id'];
            }
            Page::updateOrCreate(
                ['slug' => $slug, 'locale' => $locale],
                $payload
            );
        }

        return response()->json(['message' => 'Page updated']);
    }
}
