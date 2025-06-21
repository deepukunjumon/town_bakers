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
use App\Services\MailService;
use App\Services\OtpService;
use App\Services\WhatsAppService;

class AuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

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

        if ($user->status != DEFAULT_STATUSES['active']) {
            return response()->json([
                'success' => false,
                'error' => 'Account Inactive. Contact Support'
            ], 400);
        }

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
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $resetToken = $request->header('X-Reset-Token');

        if (!$resetToken || !$this->otpService->validateResetToken($request->email, $resetToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must be different from the old password.',
            ], 409);
        }

        try {
            DB::beginTransaction();
            $user->password = Hash::make($request->password);
            $user->save();
            DB::commit();

            $this->otpService->clearResetToken($request->email);

            $sendMail = false;
            if ($user->email) {
                $body = view('emails.password-reset-successful', ['user' => $user])->render();
                $sendMail = app(MailService::class)->send([
                    'type' => EMAIL_TYPES['PASSWORD_RESET_SUCCESS'],
                    'to' => $user->email,
                    'subject' => 'Password Resetted Successfully',
                    'body' => $body,
                    'sent_by' => 'System',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.',
                'send_mail' => $sendMail
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
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

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $otp = $this->otpService->generate();
        $this->otpService->store($user->email, $otp);

        $sendMail = false;
        if ($user->email) {
            $body = view('emails.password-reset-otp', ['user' => $user, 'otp' => $otp])->render();
            $sendMail = app(MailService::class)->send([
                'type' => EMAIL_TYPES['PASSWORD_RESET'],
                'to' => $user->email,
                'subject' => 'Password Reset OTP',
                'body' => $body,
                'sent_by' => 'System',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset OTP sent to your email',
            'send_mail' => $sendMail,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!$this->otpService->validate($request->email, $request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ], 400);
        }

        // OTP is valid, clear it and issue a reset token
        $this->otpService->clear($request->email);
        $resetToken = $this->otpService->generateResetToken($request->email);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified. Use the reset token to reset your password.',
            'reset_token' => $resetToken,
        ]);
    }
}
