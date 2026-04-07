<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Check if the URL starts with /sr
        $prefix = $request->segment(1);

        if ($prefix === 'sr') {
            App::setLocale('sr');
        } else {
            App::setLocale('en');
        }

        return $next($request);
    }
}
