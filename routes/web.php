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

    Route::get('/cesta-pitanja', [FaqController::class, 'index'])->name('faq');
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
| Legacy Shopify URL redirects → homepage (301)
|--------------------------------------------------------------------------
| The previous site was a Shopify shop with /pages, /products, /collections,
| /blogs paths. Old links/backlinks/Google index still point at them. We send
| every legacy path to the new homepage so we don't lose the inbound traffic
| or accumulate 404s. Permanent (301) so search engines transfer link equity.
*/
Route::redirect('/pages/{any}', '/', 301)->where('any', '.*');
Route::redirect('/products/{any}', '/', 301)->where('any', '.*');
Route::redirect('/collections/{any}', '/', 301)->where('any', '.*');
Route::redirect('/blogs/{any}', '/', 301)->where('any', '.*');
