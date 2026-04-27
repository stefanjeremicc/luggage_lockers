<?php

namespace App\Providers;

use App\Helpers\SiteSettings;
use App\Models\Location;
use App\Services\Lock\LockServiceInterface;
use App\Services\Lock\TTLockService;
use App\Services\Notification\EmailNotificationService;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Notification\WhatsAppNotificationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LockServiceInterface::class, TTLockService::class);

        $this->app->bind(NotificationServiceInterface::class, EmailNotificationService::class);
        $this->app->singleton('notification.email', fn ($app) => $app->make(EmailNotificationService::class));
        $this->app->singleton('notification.whatsapp', fn ($app) => $app->make(WhatsAppNotificationService::class));
    }

    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return url('/admin/reset-password?token=' . $token . '&email=' . urlencode($user->email));
        });

        view()->composer('*', function ($view) {
            $view->with('lp', app()->getLocale() === 'sr' ? 'sr.' : '');
        });

        View::share('settings', SiteSettings::all());

        View::composer('public.partials.footer', function ($view) {
            $view->with('footerLocations', Location::active()->orderBy('sort_order')->get());
        });
    }
}
