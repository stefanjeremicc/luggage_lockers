<title>@yield('title', 'Belgrade Luggage Locker — 24/7 Secure Storage')</title>
<meta name="description" content="@yield('meta_description', 'Secure 24/7 luggage storage in Belgrade. Smart lockers at 2 locations. Book online, pay on arrival. From €5.')">

<meta property="og:title" content="@yield('og_title', 'Belgrade Luggage Locker')">
<meta property="og:description" content="@yield('og_description', 'Secure 24/7 luggage storage in Belgrade. Smart lockers, book online.')">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
@if(View::hasSection('og_image'))
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
