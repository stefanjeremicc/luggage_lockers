<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogManagementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            BlogPost::with('category')
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        if (!empty($validated['is_published'])) {
            $validated['published_at'] = $validated['published_at'] ?? now();
        }

        return response()->json(BlogPost::create($validated)->load('category'), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(BlogPost::with('category')->findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $validated = $this->validatePayload($request, updating: true);

        if (!empty($validated['is_published']) && !$post->published_at) {
            $validated['published_at'] = now();
        }
        $post->update($validated);

        return response()->json($post->load('category'));
    }

    public function destroy(int $id): JsonResponse
    {
        BlogPost::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function validatePayload(Request $request, bool $updating = false): array
    {
        $required = $updating ? 'sometimes|required' : 'required';

        return $request->validate([
            'slug' => "{$required}|string|max:120|unique:blog_posts,slug" . ($updating ? ',' . $request->route('blog_post') : ''),
            'slug_sr' => 'nullable|string|max:120|unique:blog_posts,slug_sr' . ($updating ? ',' . $request->route('blog_post') : ''),
            'title' => "{$required}|string|max:255",
            'title_sr' => 'nullable|string|max:255',
            'excerpt' => "{$required}|string",
            'excerpt_sr' => 'nullable|string',
            'content' => "{$required}|string",
            'content_sr' => 'nullable|string',
            'featured_image' => 'nullable|string|max:500',
            'blog_category_id' => 'nullable|integer|exists:blog_categories,id',
            'author_name' => 'nullable|string|max:255',
            'meta_title' => "{$required}|string|max:255",
            'meta_title_sr' => 'nullable|string|max:255',
            'meta_description' => "{$required}|string|max:500",
            'meta_description_sr' => 'nullable|string|max:500',
            'is_published' => 'boolean',
        ]);
    }
}
