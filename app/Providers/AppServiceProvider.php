<?php

namespace App\Providers;

use App\Services\Lock\LockServiceInterface;
use App\Services\Lock\TTLockService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LockServiceInterface::class, TTLockService::class);
    }

    public function boot(): void
    {
        view()->composer('*', function ($view) {
            $view->with('lp', app()->getLocale() === 'sr' ? 'sr.' : '');
        });
    }
}
