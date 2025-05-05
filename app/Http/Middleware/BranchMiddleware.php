<?php

// app/Http/Middleware/BranchMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BranchMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $branchId = $request->route('branch_id');
        $userBranchId = auth()->user()->branch_id;

        if ($branchId != $userBranchId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}

