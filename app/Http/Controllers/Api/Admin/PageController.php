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
        $pages = Page::orderBy('slug')->orderBy('locale')->get()
            ->groupBy('slug')
            ->map(function ($rows, $slug) {
                return [
                    'slug' => $slug,
                    'en' => $rows->firstWhere('locale', 'en'),
                    'sr' => $rows->firstWhere('locale', 'sr'),
                ];
            })
            ->values();

        return response()->json($pages);
    }

    public function show(string $slug): JsonResponse
    {
        $rows = Page::where('slug', $slug)->get()->keyBy('locale');
        return response()->json([
            'slug' => $slug,
            'en' => $rows->get('en'),
            'sr' => $rows->get('sr'),
        ]);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'en.title' => 'nullable|string|max:200',
            'en.meta_title' => 'nullable|string|max:70',
            'en.meta_description' => 'nullable|string|max:300',
            'en.og_image' => 'nullable|string|max:500',
            'en.content' => 'nullable|string',
            'en.is_published' => 'nullable|boolean',
            'sr.title' => 'nullable|string|max:200',
            'sr.meta_title' => 'nullable|string|max:70',
            'sr.meta_description' => 'nullable|string|max:300',
            'sr.og_image' => 'nullable|string|max:500',
            'sr.content' => 'nullable|string',
            'sr.is_published' => 'nullable|boolean',
        ]);

        foreach (['en', 'sr'] as $locale) {
            if (!isset($data[$locale])) continue;
            $payload = $data[$locale];
            $payload['is_published'] = (bool) ($payload['is_published'] ?? true);
            if ($payload['is_published'] && empty(Page::where('slug', $slug)->where('locale', $locale)->value('published_at'))) {
                $payload['published_at'] = now();
            }
            Page::updateOrCreate(
                ['slug' => $slug, 'locale' => $locale],
                $payload
            );
        }

        return response()->json(['message' => 'Page updated']);
    }
}
