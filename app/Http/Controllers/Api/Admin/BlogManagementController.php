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
        return response()->json(BlogPost::orderByDesc('created_at')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string',
            'locale' => 'required|in:en,sr',
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'author_name' => 'nullable|string|max:255',
            'is_published' => 'boolean',
        ]);

        if (!empty($validated['is_published'])) {
            $validated['published_at'] = now();
        }

        return response()->json(BlogPost::create($validated), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(BlogPost::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $data = $request->all();
        if (!empty($data['is_published']) && !$post->published_at) {
            $data['published_at'] = now();
        }
        $post->update($data);
        return response()->json($post);
    }

    public function destroy(int $id): JsonResponse
    {
        BlogPost::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
