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
    | Alert endpoints are unauthenticated and send no credentials, so any
    | origin is allowed. Narrow these paths if you add session or token auth
    | in front of them.
    |
    */

    // Only the rescuee's alert endpoints need cross-origin access. The
    // rescuer dashboard is same-origin and still relies on its session cookie,
    // so it must stay out of this list.
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
