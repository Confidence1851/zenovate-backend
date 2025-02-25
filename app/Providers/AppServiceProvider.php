<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // View composer
        view()->composer("*", function ($view) {
            $view->with([
                "admin_assets" => secure_asset("admin"),
            ]);
        });

        // Force HTTPS in production
        if (app()->isProduction()) {
            \URL::forceScheme('https');
        }
    }
}
