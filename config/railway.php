<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Railway Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for Railway deployment.
    |
    */

    'trusted_proxies' => '*',
    'trusted_hosts' => ['*.railway.app', '*.up.railway.app'],

    'headers' => [
        'x-railway-edge' => 'railway/europe-west4-drams3a',
        'x-railway-request-id' => true,
        'x-content-type-options' => 'nosniff',
        'x-frame-options' => 'DENY',
        'x-xss-protection' => '1; mode=block',
    ],

    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['*'],
        'allowed_headers' => [
            'Content-Type',
            'X-Auth-Token',
            'Origin',
            'Authorization',
            'X-Requested-With',
            'Accept',
            'X-Railway-Request-Id',
            'X-Railway-Edge',
        ],
        'exposed_headers' => [
            'X-Railway-Request-Id',
            'X-Railway-Edge',
        ],
        'max_age' => 0,
        'supports_credentials' => true,
    ],
];
