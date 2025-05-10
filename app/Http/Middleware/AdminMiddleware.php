<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $tokenPayload = JWTAuth::parseToken()->getPayload();
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided or invalid.',
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        $user = $request->user();
        $tokenPayload = JWTAuth::parseToken()->getPayload();

        $role = $tokenPayload->get('role');

        if ($role === 'admin') {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }
}
