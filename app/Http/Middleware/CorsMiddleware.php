<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin', '*');

        // Allow only your frontend in dev mode
        $allowedOrigins = [
            'http://localhost:3000',
            'https://tbms.up.raiway.app', // Add production domain if needed
        ];

        $response = $request->getMethod() === 'OPTIONS'
            ? response('', 204)
            : $next($request);

        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        return $response
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization')
            ->header('Access-Control-Allow-Credentials', 'true');
    }
}
