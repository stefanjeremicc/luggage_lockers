<?php

namespace App\View\Composers;

use App\Models\BlogPost;
use App\Models\Location;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Resolves the current request's Page row (en or sr) and shares it as $pageSeo
 * with every public view, so resources/views/public/partials/seo-meta.blade.php
 * can render <title>/<meta description>/og:* from DB instead of blade hardcode.
 *
 * Mapping is driven by the route name (without the "sr." prefix) — every public
 * route maps 1:1 to a slug in the `pages` table seeded in
 * 2026_05_03_000000_seed_default_system_pages.php. Dynamic routes (locations.show,
 * blog.show, near) resolve to their bound model's SEO fields when present and
 * fall back to a stub Page object built from the model.
 */
class SeoComposer
{
    public function __construct(private Request $request) {}

    public function compose(View $view): void
    {
        $route = $this->request->route();
        $name = $route?->getName() ?? '';
        // Strip "sr." prefix so en+sr share one slug per page.
        $base = str_starts_with($name, 'sr.') ? substr($name, 3) : $name;

        $slug = match ($base) {
            'home', 'about', 'pricing', 'contact', 'faq', 'terms', 'privacy', 'blog.index' => $this->staticSlug($base),
            'locations.index' => 'locations',
            'booking.index' => 'booking-index',
            'booking.confirmation' => 'booking-confirmation',
            'booking.cancel' => 'booking-cancel',
            default => null,
        };

        $locale = app()->getLocale();
        $pageSeo = $slug ? Page::seoFor($slug, $locale) : null;

        // Dynamic-slug routes: pull SEO from the bound model when one exists.
        if (!$pageSeo) {
            $pageSeo = match ($base) {
                'locations.show' => $this->locationSeo($route?->parameter('slug'), $locale),
                'blog.show' => $this->blogPostSeo($route?->parameter('slug'), $locale),
                'near' => Page::seoFor((string) $route?->parameter('slug'), $locale),
                default => null,
            };
        }

        $view->with('pageSeo', $pageSeo);
    }

    private function staticSlug(string $base): string
    {
        return str_replace('.index', '', $base);
    }

    private function locationSeo(?string $slug, string $locale): ?Page
    {
        if (!$slug) return null;
        $loc = Location::where('slug', $slug)->orWhere('slug_sr', $slug)->first();
        if (!$loc) return null;
        return $this->stubPage([
            'meta_title' => method_exists($loc, 'metaTitleFor') ? $loc->metaTitleFor($locale) : ($loc->{"meta_title_$locale"} ?? $loc->name),
            'meta_description' => method_exists($loc, 'metaDescriptionFor') ? $loc->metaDescriptionFor($locale) : ($loc->{"meta_description_$locale"} ?? null),
            'og_image' => $loc->og_image ?? $loc->image_url ?? null,
        ]);
    }

    private function blogPostSeo(?string $slug, string $locale): ?Page
    {
        if (!$slug) return null;
        $post = BlogPost::where('slug', $slug)->orWhere('slug_sr', $slug)->first();
        if (!$post) return null;
        return $this->stubPage([
            'meta_title' => $post->meta_title ?: $post->title,
            'meta_description' => $post->meta_description ?: null,
            'og_image' => $post->og_image ?? $post->cover_image ?? null,
        ]);
    }

    private function stubPage(array $attrs): Page
    {
        $p = new Page();
        $p->setRawAttributes($attrs, true);
        return $p;
    }
}
