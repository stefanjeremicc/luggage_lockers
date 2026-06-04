@php
    $seoTitle = $pageSeo->meta_title ?? null;
    $seoDesc = $pageSeo->meta_description ?? null;
    $seoOg = $pageSeo->og_image ?? null;
    $fallbackTitle = trim((string) View::yieldContent('title')) ?: \App\Helpers\SiteSettings::siteName();
    $fallbackDesc = trim((string) View::yieldContent('meta_description'));
    $finalTitle = $seoTitle ?: $fallbackTitle;
    $finalDesc = $seoDesc ?: $fallbackDesc;
@endphp
<title>{{ $finalTitle }}</title>
<meta name="description" content="{{ $finalDesc }}">

<meta property="og:title" content="@yield('og_title', $finalTitle)">
<meta property="og:description" content="@yield('og_description', $finalDesc)">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:site_name" content="{{ \App\Helpers\SiteSettings::siteName() }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'sr' ? 'sr_RS' : 'en_US' }}">
<meta property="og:locale:alternate" content="{{ app()->getLocale() === 'sr' ? 'en_US' : 'sr_RS' }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@yield('og_title', $finalTitle)">
<meta name="twitter:description" content="@yield('og_description', $finalDesc)">
@php
    // Default OG/Twitter share image: a dedicated 1200x630 canvas with the
    // logo centered on a solid #0A0A0A background. This matches the rest of
    // the brand (dark theme) and prevents WhatsApp/FB/LinkedIn from rendering
    // the transparent logo.png on a white default — which clients hated.
    // Per-page overrides still win: $seoOg (passed by the controller) or a
    // @section('og_image') in the Blade view, but only when non-empty.
    $ogImage = url('/images/og-default.png');
    if ($seoOg) {
        $ogImage = url($seoOg);
    } elseif (View::hasSection('og_image')) {
        $yielded = trim((string) View::yieldContent('og_image'));
        if ($yielded !== '') {
            $ogImage = $yielded;
        }
    }
@endphp
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:image" content="{{ $ogImage }}">

<link rel="canonical" href="{{ url()->current() }}">

@php
    // Build hreflang alternates from the NAMED route so translated slugs
    // (e.g. /locations <-> /sr/lokacije) are resolved correctly instead of
    // naively prepending/stripping "/sr" (which produced 404 alternates).
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
    $altUrls = null;
    if ($routeName) {
        $baseName = preg_replace('/^sr\./', '', $routeName);
        $params = request()->route() ? request()->route()->parameters() : [];

        // SEO landing pages use a DIFFERENT slug per language (EN = config key,
        // SR = slug_sr), so the generic route($params) reuse would point the SR
        // alternate at the EN slug. Remap the {slug} param per language.
        if ($baseName === 'landing' && isset($params['slug'])) {
            $all = config('landing_pages', []);
            $enKey = isset($all[$params['slug']]) ? $params['slug'] : null;
            if (!$enKey) {
                foreach ($all as $k => $c) {
                    if (($c['slug_sr'] ?? null) === $params['slug']) { $enKey = $k; break; }
                }
            }
            $srSlug = $enKey ? ($all[$enKey]['slug_sr'] ?? null) : null;
            try {
                $altUrls = [
                    'en' => $enKey ? route('landing', ['slug' => $enKey]) : null,
                    'sr' => $srSlug ? route('sr.landing', ['slug' => $srSlug]) : null,
                ];
            } catch (\Throwable $e) {
                $altUrls = null;
            }
        } elseif (in_array($baseName, ['blog.show', 'locations.show'], true) && isset($params['slug'])) {
            // Blog posts and locations also have a different slug per language
            // (slug vs slug_sr); resolve the model so each alternate uses the
            // correct slug instead of reusing the current one for both.
            $model = $baseName === 'blog.show'
                ? \App\Models\BlogPost::where('slug', $params['slug'])->orWhere('slug_sr', $params['slug'])->first()
                : \App\Models\Location::where('slug', $params['slug'])->orWhere('slug_sr', $params['slug'])->first();
            if ($model) {
                try {
                    $altUrls = [
                        'en' => route($baseName, ['slug' => $model->slug]),
                        'sr' => route('sr.' . $baseName, ['slug' => $model->slug_sr ?: $model->slug]),
                    ];
                } catch (\Throwable $e) {
                    $altUrls = null;
                }
            }
        } else {
            try {
                $altUrls = [
                    'en' => route($baseName, $params),
                    'sr' => route('sr.' . $baseName, $params),
                ];
            } catch (\Throwable $e) {
                $altUrls = null; // route has no counterpart — skip alternates
            }
        }
    }
@endphp
@if($altUrls)
@if(!empty($altUrls['en']))<link rel="alternate" hreflang="en" href="{{ $altUrls['en'] }}">@endif
@if(!empty($altUrls['sr']))<link rel="alternate" hreflang="sr" href="{{ $altUrls['sr'] }}">@endif
@if(!empty($altUrls['en']))<link rel="alternate" hreflang="x-default" href="{{ $altUrls['en'] }}">@endif
@endif
