<?php

declare(strict_types=1);

return [
    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'EGP'),
    'enabled_methods' => explode(',', env('PAYMENT_ENABLED_METHODS', 'stripe,paymob,cod')),
    
    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY', ''),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],
    
    'paymob' => [
        'secret_key' => env('PAYMOB_SECRET_KEY', ''),
        'public_key' => env('PAYMOB_PUBLIC_KEY', ''),
        'api_key' => env('PAYMOB_API_KEY', ''),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET', ''),
        'integration_ids' => explode(',', env('PAYMOB_INTEGRATION_IDS', '')),
        'base_url' => env('PAYMOB_BASE_URL', 'https://eg.paymob.com'),
        'checkout_url' => env('PAYMOB_CHECKOUT_URL', 'https://eg.checkout.paymob.com'),
        'sandbox' => env('PAYMOB_SANDBOX', true),
    ],
    
    'cod' => [
        'enabled' => env('COD_ENABLED', true),
        'max_amount_minor' => env('COD_MAX_AMOUNT', 500000), // 5000 EGP
        'surcharge_minor' => env('COD_SURCHARGE', 2000), // 20 EGP
    ],
];
