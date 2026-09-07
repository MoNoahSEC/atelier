<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Automation API Configuration
    |--------------------------------------------------------------------------
    |
    | Security and rate limiting settings for the automation API layer.
    | All values are sourced from environment variables.
    |
    */

    'hmac' => [
        // Secret used to sign/verify HMAC-SHA256 automation webhook requests
        'secret'         => env('AUTOMATION_HMAC_SECRET'),
        // Maximum age of a signed request in seconds (replay protection)
        'timestamp_ttl'  => (int) env('AUTOMATION_TIMESTAMP_TTL', 300),
        // Header names carrying the signature and timestamp
        'signature_header' => env('AUTOMATION_SIGNATURE_HEADER', 'X-Automation-Signature'),
        'timestamp_header'  => env('AUTOMATION_TIMESTAMP_HEADER', 'X-Automation-Timestamp'),
    ],

    'rate_limits' => [
        // Requests per minute for automation endpoints
        'automation' => (int) env('RATE_LIMIT_AUTOMATION', 120),
        // Requests per minute for webhook ingestion
        'webhooks'   => (int) env('RATE_LIMIT_WEBHOOKS', 240),
        // Requests per minute for authenticated customers
        'customers'  => (int) env('RATE_LIMIT_CUSTOMERS', 60),
        // Requests per minute for public/unauthenticated endpoints
        'public'     => (int) env('RATE_LIMIT_PUBLIC', 30),
    ],

    'pagination' => [
        // Default page size for pending orders cursor pagination
        'pending_orders_per_page' => (int) env('AUTOMATION_PENDING_ORDERS_PER_PAGE', 100),
        'max_per_page'            => (int) env('AUTOMATION_MAX_PER_PAGE', 500),
    ],

    'sanctum' => [
        // Sanctum token ability required for automation read access
        'read_ability'  => env('AUTOMATION_READ_ABILITY', 'automation:read'),
        // Sanctum token ability required for automation write access
        'write_ability' => env('AUTOMATION_WRITE_ABILITY', 'automation:write'),
    ],

];
