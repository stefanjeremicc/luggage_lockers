<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;

class BlogController extends Controller
{
    public function index()
    {
        $posts = BlogPost::published()
            ->forLocale(app()->getLocale())
            ->paginate(12);

        return view('public.blog.index', compact('posts'));
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()
            ->forLocale(app()->getLocale())
            ->where('slug', $slug)
            ->firstOrFail();

        return view('public.blog.show', compact('post'));
    }
}
