<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class BranchMiddleware
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
        $branchIdFromToken = $tokenPayload->get('branch_id');

        if ($role === 'branch') {
            return $next($request);
        }

        if ($user && $user->branch_id === $request->branch_id) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }
}
