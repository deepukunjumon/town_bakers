<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is an admin
        if ($request->user() && $request->user()->is_admin) {
            return $next($request);
        }

        return response()->json(
            [
                'success' => false,
                'message' => 'Unauthorized'
            ],
            401
        );
    }
}
