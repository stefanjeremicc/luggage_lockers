{{-- Shared chrome for branded HTTP error pages. Falls back to a tiny inline
     stylesheet so the page renders even if Vite assets aren't built yet.

     Locale detection: SetLocale middleware doesn't fire for unmatched routes,
     so each child error view computes $isSr from the request path. --}}
<!DOCTYPE html>
<html lang="{{ ($isSr ?? false) ? 'sr' : 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code ?? 'Error' }} — {{ config('app.name', 'Belgrade Luggage Locker') }}</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        html, body { background: #0A0A0A; color: #fff; font-family: 'Inter', system-ui, sans-serif; margin: 0; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center; }
        .code { font-size: clamp(4rem, 18vw, 9rem); font-weight: 800; line-height: 1; color: #F59E0B; letter-spacing: -0.04em; margin: 0; }
        .title { font-size: clamp(1.25rem, 4vw, 1.75rem); font-weight: 600; margin: 1rem 0 0.5rem; }
        .desc  { color: #A0A0A0; max-width: 32rem; margin: 0 auto 2rem; line-height: 1.6; }
        .btn   { display: inline-block; background: #F59E0B; color: #000; padding: 0.875rem 1.75rem; border-radius: 0.5rem; font-weight: 600; text-decoration: none; }
        .btn:hover { background: #D97706; }
    </style>
</head>
<body>
    <div class="wrap">
        @yield('content')
    </div>
</body>
</html>
