<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\BranchMiddleware;

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
        'admin' => AdminMiddleware::class,
        'branch' => BranchMiddleware::class,
    ];
}
