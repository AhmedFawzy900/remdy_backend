<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Request OTP for user or admin (by email or phone).
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:user,admin',
            'method' => 'required|in:email,phone',
            'value' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $code = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(10);
        $user = null;
        $admin = null;
        if ($request->type === 'user') {
            $user = User::where($request->method, $request->value)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }
        } else {
            $admin = Admin::where($request->method, $request->value)->first();
            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Admin not found'], 404);
            }
        }
        $otp = OtpCode::create([
            'user_id' => $user?->id,
            'admin_id' => $admin?->id,
            'code' => $code,
            'type' => $request->method,
            'expires_at' => $expiresAt,
        ]);
        // Here you would send the OTP via email or SMS
        // For demo, just return the code
        return response()->json([
            'success' => true,
            'message' => 'OTP sent',
            'otp' => app()->environment('local') ? $code : null,
        ]);
    }

    /**
     * Verify OTP and login (issue Sanctum token).
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:user,admin',
            'value' => 'required|string',
            'code' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $user = null;
        $admin = null;
        if ($request->type === 'user') {
            $user = User::where('email', $request->value)->orWhere('phone', $request->value)->first();
        } else {
            $admin = Admin::where('email', $request->value)->orWhere('phone', $request->value)->first();
        }
        $otp = OtpCode::where('code', $request->code)
            ->where(function($q) use ($user, $admin) {
                if ($user) $q->where('user_id', $user->id);
                if ($admin) $q->where('admin_id', $admin->id);
            })
            ->where('expires_at', '>', now())
            ->whereNull('verified_at')
            ->first();
        if (!$otp) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP'], 401);
        }
        $otp->verified_at = now();
        $otp->save();
        if ($user) {
            $token = $user->createToken('user-token')->plainTextToken;
            return response()->json(['success' => true, 'token' => $token, 'user' => $user]);
        } else {
            $token = $admin->createToken('admin-token')->plainTextToken;
            return response()->json(['success' => true, 'token' => $token, 'admin' => $admin]);
        }
    }

    /**
     * Google login for users.
     */
    public function loginWithGoogle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->token);
            $user = User::firstOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName() ?? $googleUser->getEmail(),
                'full_name' => $googleUser->getName() ?? $googleUser->getEmail(),
                'profile_image' => $googleUser->getAvatar(),
                'password' => Hash::make(Str::random(16)),
                'account_status' => User::STATUS_ACTIVE,
            ]);
            $token = $user->createToken('user-token')->plainTextToken;
            return response()->json(['success' => true, 'token' => $token, 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Google login failed', 'error' => $e->getMessage()], 401);
        }
    }

    /**
     * Apple login for users.
     */
    public function loginWithApple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $appleUser = Socialite::driver('apple')->stateless()->userFromToken($request->token);
            $user = User::firstOrCreate([
                'email' => $appleUser->getEmail(),
            ], [
                'name' => $appleUser->getName() ?? $appleUser->getEmail(),
                'full_name' => $appleUser->getName() ?? $appleUser->getEmail(),
                'profile_image' => $appleUser->getAvatar(),
                'password' => Hash::make(Str::random(16)),
                'account_status' => User::STATUS_ACTIVE,
            ]);
            $token = $user->createToken('user-token')->plainTextToken;
            return response()->json(['success' => true, 'token' => $token, 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Apple login failed', 'error' => $e->getMessage()], 401);
        }
    }

    /**
     * Admin login: issues access and refresh tokens.
     */
    public function adminLogin(Request $request): JsonResponse
    {
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $admin = Admin::where('email', $request->email)->first();
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }
        // Access token (short-lived)
        $accessToken = $admin->createToken('admin-token')->plainTextToken;
        $accessTokenExpiresAt = now()->addHour();
        // Refresh token (long-lived)
        $refreshToken = Str::random(64);
        $refreshTokenExpiresAt = now()->addDays(30);
        \App\Models\AdminRefreshToken::create([
            'admin_id' => $admin->id,
            'token' => $refreshToken,
            'expires_at' => $refreshTokenExpiresAt,
        ]);
        return response()->json([
            'success' => true,
            'access_token' => $accessToken,
            'access_token_expires_at' => $accessTokenExpiresAt,
            'refresh_token' => $refreshToken,
            'refresh_token_expires_at' => $refreshTokenExpiresAt,
            'admin' => $admin,
        ]);
    }

    /**
     * Refresh admin access token using a valid refresh token.
     */
    public function refreshAdminToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $refresh = \App\Models\AdminRefreshToken::where('token', $request->refresh_token)
            ->where('expires_at', '>', now())
            ->first();
        if (!$refresh) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired refresh token'], 401);
        }
        $admin = $refresh->admin;
        // Issue new access token
        $accessToken = $admin->createToken('admin-token')->plainTextToken;
        $accessTokenExpiresAt = now()->addHour();
        return response()->json([
            'success' => true,
            'access_token' => $accessToken,
            'access_token_expires_at' => $accessTokenExpiresAt,
            'admin' => $admin,
        ]);
    }
} 