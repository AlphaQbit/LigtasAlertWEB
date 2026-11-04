<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
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
        // Register the Microsoft Socialite provider. The SocialiteProviders
        // manager dispatches SocialiteWasCalled during boot; without this
        // listener, Socialite::driver('microsoft') throws
        // "Driver [microsoft] not supported".
        Event::listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class);

        // Trusted hosts and proxy trust are configured in bootstrap/app.php
        // from the TRUSTED_HOSTS env var and the in-container nginx proxy.
        // URL generation reflects the real request scheme via the trusted
        // proxy, so no URL::forceScheme('https') is needed here (audit H-1).

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

        // Microsoft OAuth rate limiting:
        // - Authenticated requests (finish callback) use user ID as the key
        // - Unauthenticated requests (start, callback) fall back to IP (shared edge IP)
        // - The IP-based fallback is NOT a reliable per-client limit because all
        //   clients behind a shared-NAT platform share one egress IP. This is a
        //   conservative flood-abuse limit, not a precise per-client limit.
        // - A 5/min limit is conservative enough for alpha testing with few testers,
        //   but will block legitimate users behind shared NAT if they exceed it.
        RateLimiter::for('oauth', function (Request $request) {
            $user = $request->user() ?? $request->user('sanctum');
            if ($user) {
                return Limit::perMinute(30)->by($user->id);
            }
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
