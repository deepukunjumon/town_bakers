<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\BranchMiddleware;
use Tymon\JWTAuth\Http\Middleware\Authenticate;

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
        'jwt.auth' =>Authenticate::class,
        'admin' => AdminMiddleware::class,
        'branch' => BranchMiddleware::class,
    ];
}
