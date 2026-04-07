<?php

use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LocationController;
use App\Http\Controllers\Public\PageController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

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
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::get('/terms', [PageController::class, 'terms'])->name('terms');
    Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
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
    Route::get('/o-nama', [PageController::class, 'about'])->name('about');
    Route::get('/cenovnik', [PageController::class, 'pricing'])->name('pricing');
    Route::get('/kontakt', [PageController::class, 'contact'])->name('contact');
    Route::get('/uslovi', [PageController::class, 'terms'])->name('terms');
    Route::get('/privatnost', [PageController::class, 'privacy'])->name('privacy');
});
