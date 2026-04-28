<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Public pages live under /sr or /, so the segment-1 prefix is the
        // primary signal. API routes carry no locale prefix — they pick up the
        // locale from the X-Locale header sent by the booking flow JS, which
        // mirrors document.documentElement.lang on the page that issued the call.
        $prefix = $request->segment(1);
        $header = $request->header('X-Locale');

        $locale = 'en';
        if ($prefix === 'sr') {
            $locale = 'sr';
        } elseif ($header && in_array($header, ['sr', 'en'], true)) {
            $locale = $header;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
