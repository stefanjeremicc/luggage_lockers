<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('public.partials.seo-meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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

    @yield('scripts')
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }
    </script>
</body>
</html>
