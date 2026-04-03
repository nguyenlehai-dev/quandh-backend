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
        ResetPassword::createUrlUsing(function (object $user, string $token): string {
            $baseUrl = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');
            $email = urlencode((string) $user->getEmailForPasswordReset());

            return "{$baseUrl}/reset-password?token={$token}&email={$email}";
        });
    }
}
