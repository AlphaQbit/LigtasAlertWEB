<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust ONLY the in-container nginx reverse proxy (audit §3.4).
        // nginx authoritatively sets X-Forwarded-Proto=https, X-Forwarded-For,
        // and X-Forwarded-Host — it never copies these from client input, so
        // they cannot be spoofed. Nothing external is trusted, which keeps
        // client-supplied forwarded headers from forging the scheme or IP.
        // Render exposes HTTPS externally, so the upstream is always HTTPS
        // and Request::isSecure() reads the correct scheme.
        $middleware->trustProxies(
            at: '127.0.0.1',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_HOST
        );

        // Trusted hosts come from the TRUSTED_HOSTS env var (comma-separated).
        // Empty falls back to app.url's host. No hostname is hard-coded here;
        // the final Render hostname is unknown at build time (audit B-4).
        $middleware->trustHosts(
            array_filter(array_map('trim', explode(',', (string) env('TRUSTED_HOSTS', '')))) ?: null,
            false
        );

        // The rescuee app is served from another origin (a Flutter dev server
        // or a device), so the browser has to be allowed to call the alert API.
        // Alerts carry no credentials, so a wildcard origin is safe here.
        // ponytail: wildcard + no credentials. Pin to specific origins if you
        // ever put token auth in front of /api/alerts.
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
