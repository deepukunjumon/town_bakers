<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class BranchMiddleware
{
    public function handle(Request $request, Closure $next)
    {
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
