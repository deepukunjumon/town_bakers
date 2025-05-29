<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = new Response('', 204);
        } else {
            $response = $next($request);
        }

        // Convert to Response object if not already
        if (!$response instanceof Response) {
            $response = new Response($response);
        }

        $origin = $request->header('Origin');

        // Set CORS headers with specific values for Railway
        $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, X-Railway-Request-Id, X-Railway-Edge');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '0'); // Disable caching
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Length, Content-Range, X-Railway-Request-Id, X-Railway-Edge');

        // Prevent caching of CORS headers
        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        // Ensure content type is set correctly for API responses
        if ($response->headers->get('Content-Type') === 'text/html; charset=UTF-8') {
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }
}
