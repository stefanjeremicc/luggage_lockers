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
        try {
            $altUrls = [
                'en' => route($baseName, $params),
                'sr' => route('sr.' . $baseName, $params),
            ];
        } catch (\Throwable $e) {
            $altUrls = null; // route has no counterpart — skip alternates
        }
    }
@endphp
@if($altUrls)
<link rel="alternate" hreflang="en" href="{{ $altUrls['en'] }}">
<link rel="alternate" hreflang="sr" href="{{ $altUrls['sr'] }}">
<link rel="alternate" hreflang="x-default" href="{{ $altUrls['en'] }}">
@endif
