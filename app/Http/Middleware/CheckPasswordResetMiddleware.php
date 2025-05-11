<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CheckPasswordResetMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('api')->user();

        if ($user && Hash::check(DEFAULT_PASSWORD, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'You must reset your password before continuing.',
                'password_reset_required' => true
            ], 403);
        }

        return $next($request);
    }
}

