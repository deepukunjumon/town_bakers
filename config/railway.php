<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Railway App Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings specific to Railway.app
    | deployment environment.
    |
    */

    'trust_proxies' => true,

    'trusted_proxies' => [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.1',
        '::1',
    ],

    'trusted_hosts' => [
        '*.railway.app',
        '*.up.railway.app',
    ],

    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['*'],
        'allowed_headers' => ['*'],
        'exposed_headers' => ['Content-Length', 'Content-Range'],
        'max_age' => 86400,
        'supports_credentials' => true,
    ],
];
