<?php

namespace App\Providers;

use App\Helpers\SiteSettings;
use App\Models\Location;
use App\Services\Lock\LockServiceInterface;
use App\View\Composers\SeoComposer;
use App\Services\Lock\TTLockService;
use App\Services\Notification\EmailNotificationService;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Notification\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LockServiceInterface::class, TTLockService::class);

        $this->app->bind(NotificationServiceInterface::class, EmailNotificationService::class);
        $this->app->singleton('notification.email', fn($app) => $app->make(EmailNotificationService::class));
        $this->app->singleton('notification.whatsapp', fn($app) => $app->make(WhatsAppNotificationService::class));
    }

    public function boot(): void
    {
        // Force latin Serbian — sr maps to Cyrillic in Carbon's locale registry,
        // and the public site is latin-only. sr_Latn falls back to "fr" months
        // anyway via Symfony, so for booking/admin we use the numeric helper in
        // App\Helpers\Dates instead of translatedFormat().
        Carbon::setLocale(app()->getLocale() === 'sr' ? 'sr_Latn' : app()->getLocale());

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return url('/admin/reset-password?token=' . $token . '&email=' . urlencode($user->email));
        });

        view()->composer('*', function ($view) {
            $view->with('lp', app()->getLocale() === 'sr' ? 'sr.' : '');
        });

        if ($this->app->runningInConsole()) {
            return;
        }

        View::share('settings', SiteSettings::all());

        View::composer('public.partials.footer', function ($view) {
            $view->with('footerLocations', Location::active()->orderBy('sort_order')->get());
        });

        // Resolve per-route SEO from the pages table (and dynamic models for
        // locations.show / blog.show / near). $pageSeo flows into the head via
        // resources/views/public/partials/seo-meta.blade.php.
        View::composer('layouts.public', SeoComposer::class);
    }
}
