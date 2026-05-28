<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

/**
 * Admin sitemap tool: lists every URL in the live (dynamic) sitemap and can
 * health-check each one. The sitemap itself stays dynamic (/sitemap.xml renders
 * on every request), so this is a viewer/validator — not a generator.
 */
class SitemapController extends Controller
{
    /** Build the exact URL set the public sitemap exposes (single source of truth). */
    private function urls(): array
    {
        $locations = Location::active()->get();
        $posts = BlogPost::published()->get();

        // Render the same partial the public /sitemap.xml route uses, then pull
        // out every <loc> so this can never drift from what Google actually sees.
        $xml = view('public.partials.sitemap', compact('locations', 'posts'))->render();
        preg_match_all('/<loc>(.*?)<\/loc>/s', $xml, $m);

        return array_values(array_unique(array_map('trim', $m[1])));
    }

    /** Classify a URL for grouping in the UI. */
    private function classify(string $url): array
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $sr = $path === 'sr' || str_starts_with($path, 'sr/');
        $p = $sr ? preg_replace('#^sr/?#', '', $path) : $path;

        $type = match (true) {
            $p === ''                       => 'home',
            str_starts_with($p, 'blog')     => 'blog',
            str_starts_with($p, 'locations'), str_starts_with($p, 'lokacije') => 'location',
            str_starts_with($p, 'near'), str_starts_with($p, 'blizu') => 'near',
            in_array($p, ['locations', 'pricing', 'cenovnik', 'faq', 'najcesca-pitanja', 'about', 'o-nama', 'contact', 'kontakt', 'terms', 'uslovi', 'privacy', 'privatnost'], true) => 'static',
            default                         => 'landing',
        };

        return ['locale' => $sr ? 'sr' : 'en', 'type' => $type];
    }

    /** GET /api/admin/sitemap — the full URL list + metadata. */
    public function index(): JsonResponse
    {
        $urls = collect($this->urls())->map(fn ($u) => array_merge(['url' => $u], $this->classify($u)))->values();

        return response()->json([
            'sitemap_url' => url('/sitemap.xml'),
            'count'       => $urls->count(),
            'urls'        => $urls,
        ]);
    }

    /**
     * GET /api/admin/sitemap/check — health-check every URL.
     * Runs concurrently in chunks so checking ~70 URLs takes a few seconds.
     */
    public function check(): JsonResponse
    {
        @set_time_limit(120);
        $urls = $this->urls();
        $results = [];

        foreach (array_chunk($urls, 10) as $chunk) {
            $responses = Http::pool(fn ($pool) => array_map(
                fn ($u) => $pool->as($u)->connectTimeout(8)->timeout(12)->withoutRedirecting()
                    ->withHeaders(['User-Agent' => 'LuggageLocker-SitemapCheck/1.0'])->get($u),
                $chunk
            ));

            foreach ($chunk as $u) {
                $res = $responses[$u] ?? null;
                if ($res instanceof \Throwable || $res === null) {
                    $status = 0;
                } else {
                    try { $status = $res->status(); } catch (\Throwable $e) { $status = 0; }
                }
                $results[] = [
                    'url'      => $u,
                    'status'   => $status,
                    'ok'       => $status >= 200 && $status < 300,
                    'redirect' => $status >= 300 && $status < 400,
                    'broken'   => $status === 0 || $status >= 400,
                ];
            }
        }

        $broken = array_values(array_filter($results, fn ($r) => $r['broken']));
        $redirects = array_values(array_filter($results, fn ($r) => $r['redirect']));

        return response()->json([
            'checked'        => count($results),
            'broken_count'   => count($broken),
            'redirect_count' => count($redirects),
            'results'        => $results,
        ]);
    }
}
