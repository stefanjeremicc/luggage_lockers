<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            BlogCategory::withCount('posts')->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'name_sr' => 'nullable|string|max:100',
            'slug' => 'nullable|string|max:120',
        ]);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? $validated['name']);
        $validated['sort_order'] = (int) BlogCategory::max('sort_order') + 1;

        return response()->json(BlogCategory::create($validated), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = BlogCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'name_sr' => 'nullable|string|max:100',
            'slug' => 'nullable|string|max:120',
        ]);
        if (isset($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['slug'], $id);
        }
        $category->update($validated);
        return response()->json($category);
    }

    public function destroy(int $id): JsonResponse
    {
        BlogCategory::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:blog_categories,id',
        ]);

        foreach ($validated['ids'] as $position => $id) {
            BlogCategory::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['message' => 'Reordered']);
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);
        $slug = $base;
        $n = 2;
        while (BlogCategory::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$n}";
            $n++;
        }
        return $slug;
    }
}
