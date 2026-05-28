<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force every public request onto the single canonical host.
 *
 * The cPanel host auto-creates preview subdomains (e.g.
 * belgradeluggagelocker.com.webby.rs) and the old staging host
 * (locker.webby.rs) also served the same app — Google indexed those as
 * duplicate sites. Any host other than the canonical production domain is
 * 301-redirected to it, consolidating link equity and removing the
 * duplicates from search over time. www is folded to the bare domain too.
 *
 * Standalone PHP files in /public (deploy runners) are served directly by the
 * web server and never hit this middleware, so deploys are unaffected.
 */
class ForceCanonicalHost
{
    private const CANONICAL = 'belgradeluggagelocker.com';

    public function handle(Request $request, Closure $next): Response
    {
        // Never redirect in local dev or for console/health checks.
        if (app()->environment('local')) {
            return $next($request);
        }

        // Webhooks/callbacks must keep working on ANY host they were registered
        // with (e.g. TTLock's Callback URL may still point at the old host).
        // External senders don't follow 301s and wouldn't re-POST the body, so
        // never redirect these — let them through verbatim.
        if ($request->is('api/ttlock/callback')) {
            return $next($request);
        }

        $host = $request->getHost();
        if ($host !== self::CANONICAL) {
            return redirect()->away(
                'https://' . self::CANONICAL . $request->getRequestUri(),
                301
            );
        }

        return $next($request);
    }
}
