<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Optimization Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for query optimization
    | including cache TTLs, chunk sizes, and other performance-related settings.
    |
    */

    'cache' => [
        'enabled' => env('QUERY_CACHE_ENABLED', true),
        'ttl' => [
            'orders' => env('ORDERS_CACHE_TTL', 3600), // 1 hour
            'stock' => env('STOCK_CACHE_TTL', 3600), // 1 hour
            'items' => env('ITEMS_CACHE_TTL', 86400), // 24 hours
            'employees' => env('EMPLOYEES_CACHE_TTL', 86400), // 24 hours
        ],
    ],

    'chunk_size' => [
        'default' => env('QUERY_CHUNK_SIZE', 1000),
        'large' => env('LARGE_QUERY_CHUNK_SIZE', 5000),
    ],

    'indexes' => [
        'enabled' => env('QUERY_INDEXES_ENABLED', true),
        'tables' => [
            'orders' => [
                'branch_status_payment' => ['branch_id', 'status', 'payment_status'],
                'delivery_status' => ['delivery_date', 'status'],
                'customer_info' => ['customer_name', 'customer_email', 'customer_mobile'],
            ],
            'stock_items' => [
                'trip_item' => ['trip_id', 'item_id'],
                'quantity' => ['quantity'],
            ],
            'trips' => [
                'branch_date' => ['branch_id', 'date'],
                'employee' => ['employee_id'],
            ],
        ],
    ],

    'query_timeout' => [
        'default' => env('QUERY_TIMEOUT', 30), // seconds
        'long_running' => env('LONG_RUNNING_QUERY_TIMEOUT', 300), // 5 minutes
    ],

    'eager_loading' => [
        'enabled' => env('EAGER_LOADING_ENABLED', true),
        'max_relations' => env('MAX_EAGER_LOADING_RELATIONS', 5),
    ],
];
