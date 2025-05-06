<?php

// app/Http/Middleware/BranchMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BranchMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->branch_id === $request->branch_id) {
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
