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
    $ogImage = $seoOg ? url($seoOg) : (View::hasSection('og_image') ? trim((string) View::yieldContent('og_image')) : url('/images/logo.png'));
@endphp
<meta property="og:image" content="{{ $ogImage }}">
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
