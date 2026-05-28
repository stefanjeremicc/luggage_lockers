<?php

use App\Http\Middleware\ForceCanonicalHost;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Run first on every request: 301 any non-canonical host (cPanel
        // preview subdomains, old staging, www) to the production domain so
        // Google stops indexing duplicates.
        $middleware->prepend(ForceCanonicalHost::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
