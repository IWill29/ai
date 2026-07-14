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

    'webhook_url' => rtrim((string) env('WEBHOOK_BASE_URL', env('APP_URL', 'http://localhost')), '/').'/webhooks/shopify',

    'webhooks' => [
        'rate_limit_per_minute' => 120,
    ],

    'webhook_topics' => [
        'ORDERS_CREATE',
        'ORDERS_UPDATE',
        'ORDERS_DELETE',
        'PRODUCTS_CREATE',
        'PRODUCTS_UPDATE',
        'PRODUCTS_DELETE',
        'CUSTOMERS_CREATE',
        'CUSTOMERS_UPDATE',
        'CUSTOMERS_DELETE',
    ],

    'bulk_poll_max_attempts' => 120,
    'bulk_poll_interval_seconds' => 0,
];
