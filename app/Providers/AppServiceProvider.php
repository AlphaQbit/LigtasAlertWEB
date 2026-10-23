<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;

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
        $this->app['events']->listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class);

        // Configure rate limiters - they are registered with the RateLimiter facade
        // and used via the 'rate_limit' middleware on routes
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip());
        });

        // Alert submission: per-user (token) limit, fallback to IP.
        // The mobile API uses Sanctum bearer tokens, so resolve the user
        // through the sanctum guard rather than the default web guard.
        RateLimiter::for('alerts', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user('sanctum')?->id ?? $request->ip());
        });

        // Status updates: per-user (session) limit, fallback to IP.
        // The dashboard uses session auth, so the default web guard works.
        RateLimiter::for('status-update', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?? $request->ip());
        });
    }
}
