<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OtpCode;
use App\Mail\SignUpMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user for mobile app.
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'full_name' => 'nullable|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string|min:6',
                'profile_image' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if email already exists
            $emailExists = $this->checkEmail($request->email);
            if ($emailExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already exists'
                ], 409);
            }

            // Create user with unverified status
            $user = User::create([
                'name' => $request->name,
                'full_name' => $request->full_name ?? $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'profile_image' => $request->profile_image,
                'account_status' => User::STATUS_INACTIVE, // Will be activated after email verification
                'account_verification' => 'no',
                'subscription_plan' => User::PLAN_ROOKIE, // Set default plan to rookie
            ]);

            // Send verification email
            $this->sendVerificationEmail($user);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Please check your email for verification.',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user for mobile app.
     */
    public function login(Request $request): JsonResponse
    {
        try {
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

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if account is blocked
            if ($user->account_status === User::STATUS_BLOCKED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is blocked'
                ], 403);
            }

            // Check if account is verified
            if ($user->account_verification === 'no') {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your email first'
                ], 403);
            }

            // Check password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Create token
            $token = $user->createToken('mobile-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send verification email.
     */
    public function sendVerificationEmail(User $user): void
    {
        $code = random_int(10000, 99999);
        
        $details = [
            'code' => $code,
            'email' => $user->email,
            'type' => 'verify_code',
            'name' => $user->name
        ];

        Mail::to($user->email)->send(new SignUpMail($details));

        // Update user with OTP
        $user->update([
            'otp' => $code,
            'otp_source' => 'verify_code',
            'otp_expired_date' => Carbon::now()->addHours(24)->toDateString(),
            'code_usage' => null,
        ]);
    }

    /**
     * Send email for various operations (verification, forget password).
     */
    public function sendEmail(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'operation_type' => 'required|in:verify_code,forget_password',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $this->checkEmail($request->email);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found'
                ], 404);
            }

            // Check if user is verified for forget password
            if ($request->operation_type === 'forget_password' && $user->account_verification === 'no') {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your account first'
                ], 403);
            }

            $code = random_int(10000, 99999);
            
            $details = [
                'code' => $code,
                'email' => $request->email,
                'type' => $request->operation_type,
                'name' => $user->name
            ];

            Mail::to($request->email)->send(new SignUpMail($details));

            // Update user with OTP
            $user->update([
                'otp' => $code,
                'otp_source' => $request->operation_type,
                'otp_expired_date' => Carbon::now()->addHours(24)->toDateString(),
                'code_usage' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully. Please check your inbox.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check verification code.
     */
    public function checkCode(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'code' => 'required|string',
                'operation_type' => 'required|in:verify_code,forget_password',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $this->checkEmail($request->email);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found'
                ], 404);
            }

            return $this->validateCode($user->email, $request->code, $request->operation_type);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Code validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate code for various operations.
     */
    public function validateCode(string $email, string $code, string $key): JsonResponse
    {
        $user = $this->checkEmail($email);

        if ($user->otp == $code && 
            Carbon::now()->toDateString() <= $user->otp_expired_date &&
            $user->otp_source === $key) {

            $input = ['code_usage' => 'done'];

            if ($key === 'verify_code') {
                $input['account_verification'] = 'yes';
                $input['account_status'] = User::STATUS_ACTIVE;
            }

            $user->update($input);

            if ($key === 'forget_password') {
                return response()->json([
                    'success' => true,
                    'message' => 'Code is valid. You can now reset your password.'
                ], 200);
            } elseif ($key === 'verify_code') {
                return response()->json([
                    'success' => true,
                    'message' => 'Email verified successfully. You can now login.'
                ], 200);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired code'
        ], 400);
    }

    /**
     * Forget password functionality.
     */
    public function forgetPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string',
                'new_password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $this->checkEmail($request->email);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found'
                ], 404);
            }

            // Validate OTP
            $validationResult = $this->validateCode($user->email, $request->otp, 'forget_password');
            
            if (!$validationResult->getData()->success) {
                return $validationResult;
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile.
     */
    public function userProfile(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Prepare user data with default image if image is null
            $userData = $user->toArray();
            if (is_null($userData['profile_image'])) {
                $userData['profile_image'] = url('uploads/images/default.png');
            }

            return response()->json([
                'success' => true,
                'data' => $userData,
                'message' => 'Profile retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'full_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|max:20',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'profile_image' => 'sometimes|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $input = $request->only(['name', 'full_name', 'phone', 'email', 'profile_image']);
            $user->update($input);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Profile updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'old_password' => 'required|string',
                'new_password' => 'required|string|min:6',
                'confirm_password' => 'required|string|same:new_password',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if old password is correct
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Old password is incorrect'
                ], 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login with Google for mobile app.
     */
    public function loginWithGoogle(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_token' => 'required|string',
                'access_token' => 'required|string',
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email',
                'profile_image' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify Google ID token
            $googleUser = $this->verifyGoogleIdToken($request->id_token);
            
            if (!$googleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google token'
                ], 401);
            }

            // Check if user exists
            $user = User::where('email', $googleUser['email'])->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $request->name ?? $googleUser['name'] ?? 'Google User',
                    'full_name' => $request->name ?? $googleUser['name'] ?? 'Google User',
                    'email' => $googleUser['email'],
                    'profile_image' => $request->profile_image ?? $googleUser['picture'] ?? null,
                    'account_status' => User::STATUS_ACTIVE,
                    'account_verification' => 'yes', // Google accounts are pre-verified
                    'subscription_plan' => User::PLAN_ROOKIE,
                    'google_id' => $googleUser['sub'], // Store Google ID
                    'password' => Hash::make(Str::random(32)), // Generate random password
                ]);
            } else {
                // Update existing user with Google info
                $user->update([
                    'google_id' => $googleUser['sub'],
                    'account_status' => User::STATUS_ACTIVE,
                    'account_verification' => 'yes',
                    'profile_image' => $request->profile_image ?? $googleUser['picture'] ?? $user->profile_image,
                ]);
            }

            // Create token
            $token = $user->createToken('mobile-google-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Google login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'is_new_user' => $user->wasRecentlyCreated
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Google login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Google login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login with Apple for mobile app.
     */
    public function loginWithApple(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'identity_token' => 'required|string',
                'authorization_code' => 'required|string',
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email',
                'profile_image' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify Apple identity token
            $appleUser = $this->verifyAppleIdentityToken($request->identity_token);
            
            if (!$appleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Apple token'
                ], 401);
            }

            // Check if user exists
            $user = User::where('email', $appleUser['email'])->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $request->name ?? $appleUser['name'] ?? 'Apple User',
                    'full_name' => $request->name ?? $appleUser['name'] ?? 'Apple User',
                    'email' => $appleUser['email'],
                    'profile_image' => $request->profile_image ?? null,
                    'account_status' => User::STATUS_ACTIVE,
                    'account_verification' => 'yes', // Apple accounts are pre-verified
                    'subscription_plan' => User::PLAN_ROOKIE,
                    'apple_id' => $appleUser['sub'], // Store Apple ID
                    'password' => Hash::make(Str::random(32)), // Generate random password
                ]);
            } else {
                // Update existing user with Apple info
                $user->update([
                    'apple_id' => $appleUser['sub'],
                    'account_status' => User::STATUS_ACTIVE,
                    'account_verification' => 'yes',
                    'profile_image' => $request->profile_image ?? $user->profile_image,
                ]);
            }

            // Create token
            $token = $user->createToken('mobile-apple-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Apple login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'is_new_user' => !$user->wasRecentlyCreated
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Apple login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Apple login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Google OAuth callback for mobile app.
     */
    public function googleCallback(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string',
                'state' => 'nullable|string',
                'error' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if there's an error from Google
            if ($request->has('error')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google authentication failed',
                    'error' => $request->error
                ], 400);
            }

            // Exchange authorization code for tokens
            $tokens = $this->exchangeGoogleCodeForTokens($request->code);
            
            if (!$tokens) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to exchange authorization code for tokens'
                ], 400);
            }

            // Verify the ID token
            $googleUser = $this->verifyGoogleIdToken($tokens['id_token']);
            
            if (!$googleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google token'
                ], 401);
            }

            // Check if user exists
            $user = User::where('email', $googleUser['email'])->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $googleUser['name'] ?? 'Google User',
                    'full_name' => $googleUser['name'] ?? 'Google User',
                    'email' => $googleUser['email'],
                    'profile_image' => $googleUser['picture'] ?? null,
                    'account_status' => User::STATUS_ACTIVE,
                    'account_verification' => 'yes',
                    'subscription_plan' => User::PLAN_ROOKIE,
                    'google_id' => $googleUser['sub'],
                    'password' => Hash::make(Str::random(32)),
                ]);
            } else {
                // Update existing user with Google info
                $user->update([
                    'google_id' => $googleUser['sub'],
                    'account_status' => User::STATUS_ACTIVE,
                    'account_verification' => 'yes',
                    'profile_image' => $googleUser['picture'] ?? $user->profile_image,
                ]);
            }

            // Create token
            $token = $user->createToken('mobile-google-callback-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Google authentication successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'is_new_user' => !$user->wasRecentlyCreated,
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Google callback error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Google callback failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apple OAuth callback for mobile app.
     */
    public function appleCallback(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string',
                'state' => 'nullable|string',
                'error' => 'nullable|string',
                'id_token' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if there's an error from Apple
            if ($request->has('error')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Apple authentication failed',
                    'error' => $request->error
                ], 400);
            }

            // Exchange authorization code for tokens
            $tokens = $this->exchangeAppleCodeForTokens($request->code);
            
            if (!$tokens) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to exchange authorization code for tokens'
                ], 400);
            }

            // Verify the ID token
            $appleUser = $this->verifyAppleIdentityToken($tokens['id_token']);
            
            if (!$appleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Apple token'
                ], 401);
            }

            // Check if user exists
            $user = User::where('email', $appleUser['email'])->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $appleUser['name'] ?? 'Apple User',
                    'full_name' => $appleUser['name'] ?? 'Apple User',
                    'email' => $appleUser['email'],
                    'profile_image' => null,
                    'account_status' => User::STATUS_ACTIVE,
                    'account_verification' => 'yes',
                    'subscription_plan' => User::PLAN_ROOKIE,
                    'apple_id' => $appleUser['sub'],
                    'password' => Hash::make(Str::random(32)),
                ]);
            } else {
                // Update existing user with Apple info
                $user->update([
                    'apple_id' => $appleUser['sub'],
                    'account_status' => User::STATUS_ACTIVE,
                    'account_verification' => 'yes',
                ]);
            }

            // Create token
            $token = $user->createToken('mobile-apple-callback-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Apple authentication successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'is_new_user' => !$user->wasRecentlyCreated,
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Apple callback error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Apple callback failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify Google ID token.
     */
    private function verifyGoogleIdToken(string $idToken): ?array
    {
        try {
            $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Verify the token is for your app
                if ($data['aud'] !== config('services.google.client_id')) {
                    return null;
                }

                return [
                    'sub' => $data['sub'],
                    'email' => $data['email'],
                    'name' => $data['name'] ?? null,
                    'picture' => $data['picture'] ?? null,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Google token verification error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify Apple identity token.
     */
    private function verifyAppleIdentityToken(string $identityToken): ?array
    {
        try {
            // You should implement proper JWT verification with Apple's public keys
            // For now, this is a basic implementation
            $tokenParts = explode('.', $identityToken);
            if (count($tokenParts) !== 3) {
                return null;
            }
    
            $payload = json_decode(base64_decode(str_pad(strtr($tokenParts[1], '-_', '+/'), strlen($tokenParts[1]) % 4, '=', STR_PAD_RIGHT)), true);
    
            // Verify the token is for your app
            if ($payload['aud'] !== config('services.apple.client_id')) {
                return null;
            }
    
            // Verify token hasn't expired
            if ($payload['exp'] < time()) {
                return null;
            }
    
            return [
                'sub' => $payload['sub'],
                'email' => $payload['email'] ?? null,
                'name' => null, // Apple doesn't provide name in JWT
            ];
        } catch (\Exception $e) {
            Log::error('Apple token verification error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Exchange Google authorization code for tokens.
     */
    private function exchangeGoogleCodeForTokens(string $code): ?array
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.google.redirect'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'id_token' => $data['id_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires_in' => $data['expires_in'] ?? null,
                ];
            }

            Log::error('Google token exchange failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Google token exchange error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Exchange Apple authorization code for tokens.
     */
    private function exchangeAppleCodeForTokens(string $code): ?array
    {
        try {
            $response = Http::asForm()->post('https://appleid.apple.com/auth/token', [
                'client_id' => config('services.apple.client_id'),
                'client_secret' => $this->generateAppleClientSecret(),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.apple.redirect'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'id_token' => $data['id_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires_in' => $data['expires_in'] ?? null,
                ];
            }

            Log::error('Apple token exchange failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Apple token exchange error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate Apple client secret (JWT).
     */
    private function generateAppleClientSecret(): string
    {
        // You'll need to install firebase/php-jwt first
        // composer require firebase/php-jwt
        
        $header = [
            'alg' => 'ES256',
            'kid' => config('services.apple.key_id'),
        ];
    
        $payload = [
            'iss' => config('services.apple.team_id'),
            'iat' => time(),
            'exp' => time() + (86400 * 180), // 180 days (Apple's max)
            'aud' => 'https://appleid.apple.com',
            'sub' => config('services.apple.client_id'),
        ];
    
        $privateKey = file_get_contents(config('services.apple.private_key_path'));
        
        return JWT::encode($payload, $privateKey, 'ES256', config('services.apple.key_id'));
    }

    /**
     * Get OAuth URLs for mobile app.
     */
    public function getOAuthUrls(): JsonResponse
    {
        try {
            $googleUrl = $this->buildGoogleOAuthUrl();
            $appleUrl = $this->buildAppleOAuthUrl();

            return response()->json([
                'success' => true,
                'data' => [
                    'google' => [
                        'auth_url' => $googleUrl,
                        'client_id' => config('services.google.client_id'),
                    ],
                    'apple' => [
                        'auth_url' => $appleUrl,
                        'client_id' => config('services.apple.client_id'),
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate OAuth URLs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build Google OAuth URL.
     */
    private function buildGoogleOAuthUrl(): string
    {
        $params = [
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $this->generateState(),
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Build Apple OAuth URL.
     */
    private function buildAppleOAuthUrl(): string
    {
        $params = [
            'client_id' => config('services.apple.client_id'),
            'redirect_uri' => config('services.apple.redirect'),
            'response_type' => 'code',
            'scope' => 'name email',
            'state' => $this->generateState(),
            'response_mode' => 'form_post',
        ];

        return 'https://appleid.apple.com/auth/authorize?' . http_build_query($params);
    }

    /**
     * Generate a random state parameter for OAuth.
     */
    private function generateState(): string
    {
        return Str::random(32);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            if ($user) {
                $user->tokens()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user account.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'password' => 'required|string',
                'confirm_delete' => 'required|string|in:DELETE',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if password is correct
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password is incorrect'
                ], 400);
            }

            // Check if user confirmed deletion
            if ($request->confirm_delete !== 'DELETE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Please type DELETE to confirm account deletion'
                ], 400);
            }

            // Delete user's tokens first
            $user->tokens()->delete();

            // Delete user's reminders
            $user->reminders()->delete();

            // Delete user's favorites
            $user->favorites()->delete();

            // Delete the user account
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if email exists.
     */
    private function checkEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
} 