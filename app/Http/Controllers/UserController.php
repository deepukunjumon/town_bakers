<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserController extends Controller
{

    /**
     * Get the profile of the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->is_admin) {
                }
        return response()->json([
            'name' => $user->username ?? 'Unknown User',
            'mobile' => $user->mobile,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'user_since' => $user->created_at->format('Y-m-d'),
        ]);
    }
}
