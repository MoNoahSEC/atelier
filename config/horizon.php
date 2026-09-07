<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [

    'name' => env('HORIZON_NAME', env('APP_NAME', 'Laravel') . ' Horizon'),

    'domain' => env('HORIZON_DOMAIN'),

    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => 'horizon',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
    ),

    'middleware' => ['web'],

    'waits' => [
        'redis:products'      => env('HORIZON_WAIT_PRODUCTS', 60),
        'redis:orders'        => env('HORIZON_WAIT_ORDERS', 60),
        'redis:fulfillment'   => env('HORIZON_WAIT_FULFILLMENT', 30),
        'redis:webhooks'      => env('HORIZON_WAIT_WEBHOOKS', 30),
        'redis:shipments'     => env('HORIZON_WAIT_SHIPMENTS', 60),
        'redis:notifications' => env('HORIZON_WAIT_NOTIFICATIONS', 120),
        'redis:default'       => env('HORIZON_WAIT_DEFAULT', 120),
    ],

    'trim' => [
        'recent'        => 60,
        'pending'       => 60,
        'completed'     => 60,
        'recent_failed' => 10080,
        'failed'        => 10080,
        'monitored'     => 10080,
    ],

    'silenced'      => [],
    'silenced_tags' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job'   => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => env('HORIZON_MEMORY_LIMIT', 128),

    'defaults' => [

        'supervisor-products' => [
            'connection'          => 'redis',
            'queue'               => ['products'],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses'        => env('HORIZON_MIN_PROCESSES_PRODUCTS', 1),
            'maxProcesses'        => env('HORIZON_MAX_PROCESSES_PRODUCTS', 5),
            'balanceMaxShift'     => 1,
            'balanceCooldown'     => 3,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => env('HORIZON_TRIES_PRODUCTS', 3),
            'timeout'             => env('HORIZON_TIMEOUT_PRODUCTS', 120),
            'backoff'             => [15, 30, 60],
            'nice'                => 0,
        ],

        'supervisor-orders' => [
            'connection'          => 'redis',
            'queue'               => ['orders'],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses'        => env('HORIZON_MIN_PROCESSES_ORDERS', 1),
            'maxProcesses'        => env('HORIZON_MAX_PROCESSES_ORDERS', 5),
            'balanceMaxShift'     => 1,
            'balanceCooldown'     => 3,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => env('HORIZON_TRIES_ORDERS', 3),
            'timeout'             => env('HORIZON_TIMEOUT_ORDERS', 90),
            'backoff'             => [10, 30, 60],
            'nice'                => 0,
        ],

        'supervisor-fulfillment' => [
            'connection'          => 'redis',
            'queue'               => ['fulfillment'],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses'        => env('HORIZON_MIN_PROCESSES_FULFILLMENT', 1),
            'maxProcesses'        => env('HORIZON_MAX_PROCESSES_FULFILLMENT', 4),
            'balanceMaxShift'     => 1,
            'balanceCooldown'     => 3,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => env('HORIZON_TRIES_FULFILLMENT', 3),
            'timeout'             => env('HORIZON_TIMEOUT_FULFILLMENT', 90),
            'backoff'             => [10, 30, 60],
            'nice'                => 0,
        ],

        'supervisor-webhooks' => [
            'connection'          => 'redis',
            'queue'               => ['webhooks'],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses'        => env('HORIZON_MIN_PROCESSES_WEBHOOKS', 1),
            'maxProcesses'        => env('HORIZON_MAX_PROCESSES_WEBHOOKS', 6),
            'balanceMaxShift'     => 2,
            'balanceCooldown'     => 3,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => env('HORIZON_TRIES_WEBHOOKS', 3),
            'timeout'             => env('HORIZON_TIMEOUT_WEBHOOKS', 60),
            'backoff'             => [5, 15, 30],
            'nice'                => 0,
        ],

        'supervisor-shipments' => [
            'connection'          => 'redis',
            'queue'               => ['shipments'],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses'        => env('HORIZON_MIN_PROCESSES_SHIPMENTS', 1),
            'maxProcesses'        => env('HORIZON_MAX_PROCESSES_SHIPMENTS', 4),
            'balanceMaxShift'     => 1,
            'balanceCooldown'     => 3,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => env('HORIZON_TRIES_SHIPMENTS', 3),
            'timeout'             => env('HORIZON_TIMEOUT_SHIPMENTS', 60),
            'backoff'             => [10, 30, 60],
            'nice'                => 0,
        ],

        'supervisor-notifications' => [
            'connection'          => 'redis',
            'queue'               => ['notifications'],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses'        => env('HORIZON_MIN_PROCESSES_NOTIFICATIONS', 1),
            'maxProcesses'        => env('HORIZON_MAX_PROCESSES_NOTIFICATIONS', 3),
            'balanceMaxShift'     => 1,
            'balanceCooldown'     => 3,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => env('HORIZON_TRIES_NOTIFICATIONS', 3),
            'timeout'             => env('HORIZON_TIMEOUT_NOTIFICATIONS', 30),
            'backoff'             => [5, 15, 30],
            'nice'                => 5,
        ],

        'supervisor-default' => [
            'connection'          => 'redis',
            'queue'               => ['default'],
            'balance'             => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses'        => env('HORIZON_MIN_PROCESSES_DEFAULT', 1),
            'maxProcesses'        => env('HORIZON_MAX_PROCESSES_DEFAULT', 3),
            'balanceMaxShift'     => 1,
            'balanceCooldown'     => 3,
            'maxTime'             => 0,
            'maxJobs'             => 0,
            'memory'              => 128,
            'tries'               => env('HORIZON_TRIES_DEFAULT', 3),
            'timeout'             => env('HORIZON_TIMEOUT_DEFAULT', 60),
            'backoff'             => [10, 30, 60],
            'nice'                => 10,
        ],

    ],

    'environments' => [
        'production' => [
            'supervisor-products'      => ['minProcesses' => 2, 'maxProcesses' => 10],
            'supervisor-orders'        => ['minProcesses' => 2, 'maxProcesses' => 8],
            'supervisor-fulfillment'   => ['minProcesses' => 2, 'maxProcesses' => 6],
            'supervisor-webhooks'      => ['minProcesses' => 2, 'maxProcesses' => 10],
            'supervisor-shipments'     => ['minProcesses' => 1, 'maxProcesses' => 6],
            'supervisor-notifications' => ['minProcesses' => 1, 'maxProcesses' => 4],
            'supervisor-default'       => ['minProcesses' => 1, 'maxProcesses' => 4],
        ],

        'staging' => [
            'supervisor-products'      => ['minProcesses' => 1, 'maxProcesses' => 4],
            'supervisor-orders'        => ['minProcesses' => 1, 'maxProcesses' => 4],
            'supervisor-fulfillment'   => ['minProcesses' => 1, 'maxProcesses' => 3],
            'supervisor-webhooks'      => ['minProcesses' => 1, 'maxProcesses' => 4],
            'supervisor-shipments'     => ['minProcesses' => 1, 'maxProcesses' => 3],
            'supervisor-notifications' => ['minProcesses' => 1, 'maxProcesses' => 2],
            'supervisor-default'       => ['minProcesses' => 1, 'maxProcesses' => 2],
        ],

        'local' => [
            'supervisor-products'      => ['minProcesses' => 1, 'maxProcesses' => 2],
            'supervisor-orders'        => ['minProcesses' => 1, 'maxProcesses' => 2],
            'supervisor-fulfillment'   => ['minProcesses' => 1, 'maxProcesses' => 2],
            'supervisor-webhooks'      => ['minProcesses' => 1, 'maxProcesses' => 2],
            'supervisor-shipments'     => ['minProcesses' => 1, 'maxProcesses' => 2],
            'supervisor-notifications' => ['minProcesses' => 1, 'maxProcesses' => 1],
            'supervisor-default'       => ['minProcesses' => 1, 'maxProcesses' => 1],
        ],
    ],

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],

];
