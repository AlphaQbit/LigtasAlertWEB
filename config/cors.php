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
    */

    // Only the rescuee's alert endpoints need cross-origin access. The
    // rescuer dashboard is same-origin and still relies on its session cookie,
    // so it must stay out of this list.
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
