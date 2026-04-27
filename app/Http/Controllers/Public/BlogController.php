<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;

class BlogController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();

        $rawPosts = BlogPost::published()->with('category')->paginate(12);
        $posts = $rawPosts->through(fn ($p) => $this->presentCard($p, $locale));

        return view('public.blog.index', compact('posts'));
    }

    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $raw = BlogPost::published()->with('category')
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('slug_sr', $slug);
            })
            ->firstOrFail();

        $post = $this->presentFull($raw, $locale);

        return view('public.blog.show', compact('post'));
    }

    private function presentCard(BlogPost $p, string $locale): object
    {
        return (object) [
            'slug' => $p->slugFor($locale),
            'title' => $p->titleFor($locale),
            'excerpt' => $p->excerptFor($locale),
            'featured_image' => $p->featured_image,
            'category' => $this->categoryName($p, $locale),
            'published_at' => $p->published_at,
        ];
    }

    private function presentFull(BlogPost $p, string $locale): object
    {
        return (object) [
            'slug' => $p->slugFor($locale),
            'title' => $p->titleFor($locale),
            'excerpt' => $p->excerptFor($locale),
            'content' => $p->contentFor($locale),
            'featured_image' => $p->featured_image,
            'category' => $this->categoryName($p, $locale),
            'author_name' => $p->author_name,
            'published_at' => $p->published_at,
            'meta_title' => $p->metaTitleFor($locale),
            'meta_description' => $p->metaDescriptionFor($locale),
        ];
    }

    private function categoryName(BlogPost $p, string $locale): ?string
    {
        if (!$p->category) return null;
        return $locale === 'sr' && $p->category->name_sr
            ? $p->category->name_sr
            : $p->category->name;
    }
}
