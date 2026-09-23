<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $frontendUrl = config('cors.allowed_origins.0', 'http://localhost:5173');
            return rtrim($frontendUrl, '/') . '/?token=' . urlencode($token) . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
