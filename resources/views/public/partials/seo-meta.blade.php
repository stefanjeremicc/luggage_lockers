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
@if($seoOg)
<meta property="og:image" content="{{ url($seoOg) }}">
<meta name="twitter:image" content="{{ url($seoOg) }}">
@elseif(View::hasSection('og_image'))
<meta property="og:image" content="@yield('og_image')">
@endif

<link rel="canonical" href="{{ url()->current() }}">

@if(app()->getLocale() === 'en')
<link rel="alternate" hreflang="sr" href="{{ url('/sr' . request()->getPathInfo()) }}">
<link rel="alternate" hreflang="en" href="{{ url()->current() }}">
@else
<link rel="alternate" hreflang="en" href="{{ str_replace('/sr', '', url()->current()) ?: url('/') }}">
<link rel="alternate" hreflang="sr" href="{{ url()->current() }}">
@endif
