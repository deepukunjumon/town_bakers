<?php

use App\Http\Middleware\AdminMiddleware;

class HttpMiddleware
{
    protected $middlewareGroups = [
        'web' => [
            // Your web middleware
        ],

        'api' => [
            // Your API middleware
        ],
    ];

    protected $routeMiddleware = [
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'branch' => \App\Http\Middleware\BranchMiddleware::class,
    ];
}

