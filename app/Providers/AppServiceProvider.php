<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        $isLocalHost = isset($_SERVER['HTTP_HOST']) && (
            str_starts_with($_SERVER['HTTP_HOST'], 'localhost') ||
            str_starts_with($_SERVER['HTTP_HOST'], '127.0.0.1')
        );

        if (!$isLocalHost && !app()->environment('local', 'testing')) {
            if (
                app()->environment('production') ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                (isset($_SERVER['SERVER_NAME']) && str_contains($_SERVER['SERVER_NAME'], 'spilms.tech')) ||
                (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'spilms.tech'))
            ) {
                URL::forceScheme('https');
            }
        }
    }
}
