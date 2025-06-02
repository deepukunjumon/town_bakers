<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', env('CORS_ALLOWED_ORIGINS', '*'));
        $response->headers->set('Access-Control-Allow-Methods', env('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS'));
        $response->headers->set('Access-Control-Allow-Headers', env('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN'));
        $response->headers->set('Access-Control-Allow-Credentials', env('CORS_ALLOWED_CREDENTIALS', 'true'));
        $response->headers->set('Access-Control-Max-Age', env('CORS_MAX_AGE', '86400'));

        if ($request->isMethod('OPTIONS')) {
            $response->setStatusCode(200);
            $response->setContent('');
        }

        return $response;
    }
} 