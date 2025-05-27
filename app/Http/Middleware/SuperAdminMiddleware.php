<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class SuperAdminMiddleware
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

        if (in_array($role, ['super_admin'])) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }
}
