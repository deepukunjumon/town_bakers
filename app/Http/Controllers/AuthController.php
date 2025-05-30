<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;

class AuthController extends Controller
{
    /**
     * Handle user login and return JWT token.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid Credentials'
            ], 401);
        }

        $user = Auth::guard('api')->user();

        $passwordResetRequired = Hash::check(DEFAULT_PASSWORD, $user->password);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'password_reset_required' => $passwordResetRequired,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role
            ]
        ]);
    }

    /**
     * Handle password reset.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::guard('api')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must be different from current password',
            ], 400);
        }

        if ($request->new_password === DEFAULT_PASSWORD) {
            return response()->json([
                'success' => false,
                'message' => 'New password cannot be same as the default password',
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully. Please log in again.',
        ]);
    }

    /**
     * Handle user logout.
     *
     * @return JsonResponse
     */

    public function logout()
    {
        try {
            if (!JWTAuth::getToken()) {
                return response()->json(['error' => 'No token provided'], 400);
            }

            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Logged out successfully']);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token could not be parsed'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to logout, please try again'], 500);
        }
    }

    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json(['token' => $token]);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to refresh token'], 500);
        }
    }

    public function protectedRoute()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json(['user' => $user]);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token could not be parsed'], 500);
        }
    }

    /**
     * Send password reset link.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Generate a random token
        $token = Str::random(64);
        
        // Store the token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        try {
            // Send the password reset email
            Mail::to($request->email)->send(new PasswordResetMail($token, $request->email));

            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
