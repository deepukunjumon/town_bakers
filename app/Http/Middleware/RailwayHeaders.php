<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RailwayHeaders
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
        $response = $next($request);

        if (!$response instanceof Response) {
            $response = new Response($response);
        }

        // Set Railway-specific headers
        $response->headers->set('X-Railway-Edge', 'railway/europe-west4-drams3a');
        $response->headers->set('X-Railway-Request-Id', $request->header('X-Railway-Request-Id', uniqid()));

        // Set CORS headers that Railway might strip
        $response->headers->set('Access-Control-Allow-Origin', $request->header('Origin', '*'));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, X-Railway-Request-Id');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Length, Content-Range, X-Railway-Request-Id');

        // Ensure content type is set correctly
        if ($response->headers->get('Content-Type') === 'text/html; charset=UTF-8') {
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }
}
