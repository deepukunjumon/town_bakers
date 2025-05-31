<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Tymon\JWTAuth\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckPasswordResetMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\BranchMiddleware;
use App\Http\Middleware\CheckTokenMiddleware;

$constantsPath = __DIR__ . '/../app/constants.php';
if (file_exists($constantsPath)) {
    require_once $constantsPath;
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt.auth' => Authenticate::class,
            'super-admin' => SuperAdminMiddleware::class,
            'admin' => AdminMiddleware::class,
            'branch' => BranchMiddleware::class,
            'check.password.reset' => CheckPasswordResetMiddleware::class,
            'check.token' => CheckTokenMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 
    })->create();
