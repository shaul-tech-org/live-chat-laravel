<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // General API: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Room creation: 5 requests per minute
        RateLimiter::for('room-creation', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Message send: 30 requests per minute
        RateLimiter::for('message-send', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Typing indicator: 20 requests per minute
        RateLimiter::for('typing-indicator', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        // Admin API: 120 requests per minute
        RateLimiter::for('admin-api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Auth login: 5 requests per minute
        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
