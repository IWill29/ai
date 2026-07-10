<?php

declare(strict_types=1);

return [
    'api_version' => env('SHOPIFY_API_VERSION', '2026-04'),

    'scopes' => [
        'read_orders',
        'write_orders',
        'read_products',
        'write_products',
        'read_customers',
        'write_customers',
        'read_inventory',
        'write_inventory',
    ],

    'timeout' => 15,
    'max_retries' => 3,
    'throttle_max_retries' => 2,
    'circuit_breaker_threshold' => 5,
    'circuit_breaker_ttl_seconds' => 60,
];
