<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Eager preconnects: open TCP+TLS to every third-party origin we will
         contact, BEFORE we issue any request to them. Order matters — these
         must come before the actual <script>/<link> requests to those hosts so
         the connection is warm when the loader fires. Saves ~100-200ms per
         origin on a fresh visit. --}}
    <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
    <link rel="preconnect" href="https://www.google-analytics.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Google Analytics 4 (gtag.js) + Google Ads conversion tracking.
         Both share the same gtag() global — Google's documented pattern is one
         script src (any of the IDs works as the loader) and a config() call per
         property. We use the GA4 ID as the script-src loader since GA4 fires on
         every pageview anyway; the Ads property piggy-backs without a second
         network round-trip. --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-SG2FY87HPF"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-SG2FY87HPF');
        gtag('config', 'AW-17053113929');
        // Client's own GA4 property — piggy-backs on the same gtag loader above
        // (one script src, extra config() call). Do NOT paste the client's full
        // snippet: it ships a duplicate gtag/js loader.
        gtag('config', 'G-LDMVH3BRCB');
    </script>

    @include('public.partials.seo-meta')

    {{-- Load the font stylesheet asynchronously so it doesn't block first paint
         (display=swap shows fallback text immediately, then swaps to Inter).
         Preconnects to fonts.googleapis.com + fonts.gstatic.com are issued
         above (before the gtag script) so the TCP+TLS handshake is done by
         the time this preload fires. --}}
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"></noscript>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#F59E0B">

    @vite(['resources/css/public.css', 'resources/js/public/app.js'])

    @yield('head')
</head>
<body class="min-h-screen flex flex-col">
    @include('public.partials.header')

    <main class="flex-1 pt-20">
        @yield('content')
    </main>

    @include('public.partials.footer')

    @stack('schema')
    @include('public.partials.schema-markup')
    @include('public.partials.breadcrumb-schema')

    @yield('scripts')
    @stack('scripts')
    {{-- Service worker registration removed on purpose. /sw.js is now a kill
         switch that purges caches + unregisters itself for any client that still
         has the old caching SW (it caused stale-bundle white screens). Do NOT
         re-add register('/sw.js') unless the SW is redesigned. --}}
</body>
</html>
