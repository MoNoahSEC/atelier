<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Native apps and the production storefront should be explicit; wildcard origins are not safe for an authenticated shop.
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', 'https://atelier404.store,https://www.atelier404.store,capacitor://localhost,http://localhost'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Correlation-Id'],

    'max_age' => 3600,

    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),
];
