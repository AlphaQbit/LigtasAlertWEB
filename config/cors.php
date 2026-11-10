<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The rescuee app runs on its own origin - a Flutter dev server, or a
    | device hitting your LAN address - so the browser needs permission to
    | call the alert API. Laravel's HandleCors middleware reads this file.
    |
    | CORS is scoped to api/* only; the rescuer dashboard is same-origin and
    | still relies on its session cookie, so it must stay out of this list.
    |
    | ALLOWED_ORIGINS is env-driven and REQUIRED in production. With the env
    | unset, allowed_origins evaluates to an empty array and the CORS service
    | rejects every origin - the rescuee app's calls are blocked by the browser.
    | List the rescuee app's origin(s), comma-separated. Alerts carry no
    | credentials (bearer tokens, no cookies), so supports_credentials stays
    | false. Narrow these paths if you add session or token auth in front of
    | them.
    |
    | For local development, allow any localhost/127.0.0.1 port so that
    | `flutter run -d chrome` (which picks a random port) works without
    | requiring --web-port. In production, only explicit origins are allowed.
    |
    */

    // Only the rescuee's alert endpoints need cross-origin access. The
    // rescuer dashboard is same-origin and still relies on its session cookie,
    // so it must stay out of this list.
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),

    // In local development (APP_DEBUG=true), allow any port on localhost/127.0.0.1
    // so that Flutter's random port assignment works with `flutter run -d chrome`.
    'allowed_origins_patterns' => env('APP_DEBUG', false)
        ? [
            '/^https?:\/\/localhost(:\d+)?$/',
            '/^https?:\/\/127\.0\.0\.1(:\d+)?$/',
        ]
        : [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
