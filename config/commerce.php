<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Commerce Configuration
    |--------------------------------------------------------------------------
    */

    'currency' => env('STORE_CURRENCY', 'USD'),
    'locale'   => env('STORE_LOCALE', 'en'),

    'tax' => [
        // Flat rate as a decimal (0.15 = 15%). Set to 0 to disable.
        'flat_rate' => (float) env('TAX_FLAT_RATE', 0.0),
        // Whether retail prices are tax-inclusive
        'inclusive'  => (bool) env('TAX_INCLUSIVE', false),
    ],

    'shipping' => [
        // Free shipping threshold in smallest currency unit (e.g. cents)
        'free_threshold_minor' => (int) env('FREE_SHIPPING_THRESHOLD_MINOR', 0),
    ],

    'cart' => [
        // How many minutes a guest cart session is valid
        'session_ttl_minutes' => (int) env('CART_SESSION_TTL_MINUTES', 1440),
        'max_item_quantity'   => (int) env('CART_MAX_ITEM_QUANTITY', 10),
    ],

    'payment' => [
        // Name of the active PaymentProvider binding (resolved in AppServiceProvider)
        'provider' => env('PAYMENT_PROVIDER', 'null'),
    ],

    'cod' => [
        // Cash on Delivery surcharge in smallest currency unit (e.g. 2000 = 20.00 EGP)
        'surcharge_minor' => (int) env('COD_SURCHARGE', 2000),
        // Max order amount allowed for COD (in minor units)
        'max_amount_minor' => (int) env('COD_MAX_AMOUNT', 500000),
        'enabled'          => (bool) env('COD_ENABLED', true),
    ],

    'inventory' => [
        // Allow orders when inventory reaches this threshold (0 = strict, no oversell)
        'oversell_buffer' => (int) env('INVENTORY_OVERSELL_BUFFER', 0),
    ],
];
