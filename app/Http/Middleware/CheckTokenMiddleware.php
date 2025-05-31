<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (!$request->bearerToken()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            JWTAuth::parseToken()->authenticate();
            return $next($request);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid or expired.',
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
} 