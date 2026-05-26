<?php

use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LocationController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\ReviewController as PublicReviewController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

// Temp diagnostic — remove after DNS cutover (Phase 4)
Route::get('/health-check', fn () => [
    'host' => request()->getHost(),
    'app_url' => config('app.url'),
    'scheme' => request()->getScheme(),
    'time' => now()->toIso8601String(),
]);

// robots.txt — dynamic so the Sitemap directive uses the request's host (works
// transparently for staging subdomains, no per-environment file edits needed).
Route::get('/robots.txt', function () {
    $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api\nDisallow: /booking/\nDisallow: /sr/rezervacija/\n\nSitemap: " . url('/sitemap.xml') . "\n";
    return response($body, 200)->header('Content-Type', 'text/plain');
})->name('robots');

// Sitemap
Route::get('/sitemap.xml', function () {
    $locations = \App\Models\Location::active()->get();
    $posts = \App\Models\BlogPost::published()->get();

    $content = view('public.partials.sitemap', compact('locations', 'posts'))->render();
    return response($content, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

// Admin SPA catch-all
Route::get('/admin/{any?}', function () {
    return view('admin.spa');
})->where('any', '.*')->name('admin');

// English routes (no prefix)
Route::middleware(SetLocale::class)->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::get('/locations/{slug}', [LocationController::class, 'show'])->name('locations.show');

    Route::get('/locations/{slug}/book', [BookingController::class, 'index'])->name('booking.index');
    Route::get('/booking/{uuid}/confirmation', [BookingController::class, 'confirmation'])->name('booking.confirmation');
    Route::get('/booking/{uuid}/cancel', [BookingController::class, 'cancelForm'])->name('booking.cancel');

    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

    Route::get('/faq', [FaqController::class, 'index'])->name('faq');
    Route::post('/reviews', [PublicReviewController::class, 'store'])->name('reviews.store');
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::get('/terms', [PageController::class, 'terms'])->name('terms');
    Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');

    // SEO landing pages targeting points of interest
    Route::get('/near/{slug}', [PageController::class, 'near'])->name('near');
});

// Serbian routes with /sr prefix and Serbian slugs
Route::prefix('sr')->middleware(SetLocale::class)->name('sr.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/lokacije', [LocationController::class, 'index'])->name('locations.index');
    Route::get('/lokacije/{slug}', [LocationController::class, 'show'])->name('locations.show');

    Route::get('/lokacije/{slug}/rezervisi', [BookingController::class, 'index'])->name('booking.index');
    Route::get('/rezervacija/{uuid}/potvrda', [BookingController::class, 'confirmation'])->name('booking.confirmation');
    Route::get('/rezervacija/{uuid}/otkazi', [BookingController::class, 'cancelForm'])->name('booking.cancel');

    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

    Route::get('/najcesca-pitanja', [FaqController::class, 'index'])->name('faq');
    Route::post('/recenzije', [PublicReviewController::class, 'store'])->name('reviews.store');
    Route::get('/o-nama', [PageController::class, 'about'])->name('about');
    Route::get('/cenovnik', [PageController::class, 'pricing'])->name('pricing');
    Route::get('/kontakt', [PageController::class, 'contact'])->name('contact');
    Route::get('/uslovi', [PageController::class, 'terms'])->name('terms');
    Route::get('/privatnost', [PageController::class, 'privacy'])->name('privacy');

    // SEO landing pages (SR)
    Route::get('/blizu/{slug}', [PageController::class, 'near'])->name('near');
});

/*
|--------------------------------------------------------------------------
| Legacy Shopify URL redirects (301)
|--------------------------------------------------------------------------
| The previous site was a Shopify shop with /pages, /products, /collections,
| /blogs paths. Old backlinks + Google's index still point at them. Rather
| than blanket-redirecting everything to the homepage (which Google treats as
| a "soft 404" and won't pass full link equity), we map each legacy path to
| the most topically-relevant new page, falling back to the homepage only for
| genuinely unknown URLs. Permanent (301) so search engines transfer equity.
*/
// Old Shopify "pages" → closest new content page (known slugs), else homepage.
Route::get('/pages/{slug}', function (string $slug) {
    $map = [
        'what-to-visit'        => '/blog',
        'about-us'             => '/about',
        'about'                => '/about',
        'contact'              => '/contact',
        'contact-us'           => '/contact',
        'faq'                  => '/faq',
        'faqs'                 => '/faq',
        'pricing'              => '/pricing',
        'prices'               => '/pricing',
        'terms'                => '/terms',
        'terms-of-service'     => '/terms',
        'terms-and-conditions' => '/terms',
        'privacy'              => '/privacy',
        'privacy-policy'       => '/privacy',
    ];
    return redirect($map[strtolower($slug)] ?? '/', 301);
})->where('slug', '.*');

// Per-locker "products" had no 1:1 equivalent → send to the locations browser
// (real content page that leads into the booking flow).
Route::redirect('/products/{any}', '/locations', 301)->where('any', '.*');
// Shopify collections → locations listing.
Route::redirect('/collections/{any}', '/locations', 301)->where('any', '.*');
// Shopify blog → new blog index.
Route::redirect('/blogs/{any}', '/blog', 301)->where('any', '.*');
