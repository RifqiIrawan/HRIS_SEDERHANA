<?php

namespace App\Providers;

use App\Services\MenuAccessService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Shared for the request: the middleware and the sidebar both ask it,
        // and a per-resolution instance would read the menu table twice.
        $this->app->singleton(MenuAccessService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
