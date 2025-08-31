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
use Laravel\Socialite\Facades\Socialite;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\JWK;

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
    public function loginWithGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Login with Apple for mobile app.
     */
    public function loginWithApple()
    {
        try {
            return Socialite::driver('apple')->stateless()->redirect();
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
    public function googleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = User::firstOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                'profile_image' => $googleUser->getAvatar(),
                'account_status' => User::STATUS_ACTIVE,
                'account_verification' => 'yes',
                'subscription_plan' => User::PLAN_ROOKIE,
                'google_id' => $googleUser->getId(),
               
            ]);
             // Generate token for API
            $token = $user->createToken('mobile-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Google authentication successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'is_new_user' => !$user->wasRecentlyCreated,
                    'access_token' => $token,
                    'refresh_token' => $token,
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
            $appleUser = Socialite::driver('apple')->stateless()->user();

            $user = User::firstOrCreate([
                'email' => $appleUser->getEmail(),
            ], [
                'name' => $appleUser->getName(),
                'profile_image' => $appleUser->getAvatar(),
                'account_status' => User::STATUS_ACTIVE,
                'account_verification' => 'yes',
                'subscription_plan' => User::PLAN_ROOKIE,
                'apple_id' => $appleUser->getId(),
            ]);

            // Generate token for API
            $token = $user->createToken('mobile-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Apple authentication successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'is_new_user' => !$user->wasRecentlyCreated,
                    'access_token' => $token,
                    'refresh_token' => $token,
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



        /**
     * Handle Google token authentication for mobile app.
     */
    public function googleTokenAuth(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'access_token' => 'required|string',
                'id_token' => 'nullable|string', // Optional but recommended for better security
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get user info from Google using the access token
            $googleUser = $this->getGoogleUserFromToken($request->access_token);
            
            if (!$googleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google access token'
                ], 401);
            }

            // Verify ID token if provided (additional security)
            if ($request->id_token) {
                $idTokenData = $this->verifyGoogleIdToken($request->id_token);
                if (!$idTokenData || $idTokenData['sub'] !== $googleUser['id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid Google ID token'
                    ], 401);
                }
            }

            // Find or create user
            $user = User::where('google_id', $googleUser['id'])
                       ->orWhere('email', $googleUser['email'])
                       ->first();

            if ($user) {
                // Update existing user with Google data if not already set
                $this->updateUserGoogleData($user, $googleUser);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser['name'] ?? 'Google User',
                    'full_name' => $googleUser['name'] ?? 'Google User',
                    'email' => $googleUser['email'],
                    'profile_image' => $googleUser['picture'] ?? null,
                    'google_id' => $googleUser['id'],
                    'account_status' => User::STATUS_ACTIVE,
                    'account_verification' => 'yes',
                    'subscription_plan' => User::PLAN_ROOKIE,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]);
            }

            // Generate API token
            $token = $user->createToken('mobile-google-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Google authentication successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'is_new_user' => $user->wasRecentlyCreated ?? false,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Google token auth error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Google authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Apple token authentication for mobile app.
     */
    public function appleTokenAuth(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'identity_token' => 'required|string',
                'user_data' => 'nullable|array', // Optional user data from Apple
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify Apple identity token with proper JWT verification
            $appleUser = $this->verifyAppleIdentityToken($request->identity_token);
            
            if (!$appleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Apple identity token'
                ], 401);
            }

            // Extract email from verified token
            $email = $appleUser['email'] ?? null;
            $appleId = $appleUser['sub'] ?? null;

            if (!$email || !$appleId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Required user data not found in Apple token'
                ], 400);
            }

            // Find or create user
            $user = User::where('apple_id', $appleId)
                       ->orWhere('email', $email)
                       ->first();

            if ($user) {
                // Update existing user with Apple data if not already set
                $this->updateUserAppleData($user, $appleUser);
            } else {
                // Handle user name (Apple only provides it on first auth)
                $name = $this->extractUserNameFromAppleData($request->user_data);

                // Create new user
                $user = User::create([
                    'name' => $name,
                    'full_name' => $name,
                    'email' => $email,
                    'apple_id' => $appleId,
                    'account_status' => User::STATUS_ACTIVE,
                    'account_verification' => 'yes',
                    'subscription_plan' => User::PLAN_ROOKIE,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)), // Random password for OAuth users
                ]);
            }

            // Generate API token
            $token = $user->createToken('mobile-apple-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Apple authentication successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'is_new_user' => $user->wasRecentlyCreated ?? false,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Apple token auth error: ' . $e->getMessage(), [
                'token' => substr($request->identity_token ?? '', 0, 50) . '...',
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Apple authentication failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Authentication failed'
            ], 500);
        }
    }

    /**
     * Get Google user data from access token.
     */
    private function getGoogleUserFromToken(string $accessToken): ?array
    {
        try {
            $response = Http::get('https://www.googleapis.com/oauth2/v1/userinfo', [
                'access_token' => $accessToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'id' => $data['id'],
                    'email' => $data['email'],
                    'name' => $data['name'] ?? null,
                    'picture' => $data['picture'] ?? null,
                    'verified_email' => $data['verified_email'] ?? false,
                ];
            }

            Log::error('Google user info request failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Google user info error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update existing user with Google data.
     */
    private function updateUserGoogleData(User $user, array $googleUser): void
    {
        $updateData = [];

        if (!$user->google_id) {
            $updateData['google_id'] = $googleUser['id'];
        }

        if (!$user->profile_image && !empty($googleUser['picture'])) {
            $updateData['profile_image'] = $googleUser['picture'];
        }

        if (!$user->email_verified_at && !empty($googleUser['verified_email'])) {
            $updateData['email_verified_at'] = now();
            $updateData['account_verification'] = 'yes';
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }
    }

    /**
     * Update existing user with Apple data.
     */
    private function updateUserAppleData(User $user, array $appleUser): void
    {
        $updateData = [];

        if (!$user->apple_id) {
            $updateData['apple_id'] = $appleUser['sub'];
        }

        if (!$user->email_verified_at) {
            $updateData['email_verified_at'] = now();
            $updateData['account_verification'] = 'yes';
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }
    }

    /**
     * Verify Google ID token (enhanced security).
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
                    Log::error('Google ID token audience mismatch');
                    return null;
                }

                // Verify token hasn't expired
                if ($data['exp'] < time()) {
                    Log::error('Google ID token expired');
                    return null;
                }

                return [
                    'sub' => $data['sub'],
                    'email' => $data['email'],
                    'name' => $data['name'] ?? null,
                    'picture' => $data['picture'] ?? null,
                    'email_verified' => $data['email_verified'] ?? false,
                ];
            }

            Log::error('Google ID token verification failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Google ID token verification error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify Apple identity token with proper JWT signature verification.
     */
    private function verifyAppleIdentityToken(string $identityToken): ?array
    {
        try {
            // Decode JWT header to get key ID
            $header = $this->decodeJWTHeader($identityToken);
            if (!$header || !isset($header['kid']) || !isset($header['alg'])) {
                Log::error('Apple identity token: Invalid JWT header', ['header' => $header]);
                return null;
            }

            // Validate algorithm
            if ($header['alg'] !== 'RS256') {
                Log::error('Apple identity token: Unsupported algorithm', ['alg' => $header['alg']]);
                return null;
            }

            // Get Apple's public keys
            $appleKeys = $this->getApplePublicKeys();
            if (!$appleKeys) {
                Log::error('Apple identity token: Failed to fetch Apple public keys');
                return null;
            }

            // Find the correct public key using key ID
            $publicKey = null;
            foreach ($appleKeys['keys'] as $key) {
                if ($key['kid'] === $header['kid']) {
                    $publicKey = $key;
                    break;
                }
            }

            if (!$publicKey) {
                Log::error('Apple identity token: Public key not found', ['kid' => $header['kid']]);
                return null;
            }

            // Convert JWK to PEM format and verify JWT
            $keySet = [$header['kid'] => $publicKey];
            $jwkSet = JWK::parseKeySet(['keys' => array_values($keySet)]);
            
            // Decode and verify JWT
            $decoded = JWT::decode($identityToken, $jwkSet);
            $payload = (array) $decoded;

            // Validate token claims
            $validationResult = $this->validateAppleTokenClaims($payload);
            if (!$validationResult['valid']) {
                Log::error('Apple identity token: Claims validation failed', [
                    'reason' => $validationResult['reason'],
                    'payload' => $payload
                ]);
                return null;
            }

            Log::info('Apple identity token verified successfully', [
                'sub' => $payload['sub'],
                'email' => $payload['email'] ?? 'not_provided'
            ]);

            return [
                'sub' => $payload['sub'],
                'email' => $payload['email'] ?? null,
                'email_verified' => $payload['email_verified'] ?? false,
                'iss' => $payload['iss'],
                'aud' => $payload['aud'],
                'exp' => $payload['exp'],
                'iat' => $payload['iat'],
            ];

        } catch (\Firebase\JWT\ExpiredException $e) {
            Log::error('Apple identity token expired', ['error' => $e->getMessage()]);
            return null;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            Log::error('Apple identity token signature invalid', ['error' => $e->getMessage()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Apple identity token verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Fetch Apple's public keys from JWKS endpoint.
     */
    private function getApplePublicKeys(): ?array
    {
        try {
            $cacheKey = 'apple_public_keys';
            $cacheTtl = 3600; // Cache for 1 hour

            // Try to get from cache first
            if (cache()->has($cacheKey)) {
                return cache($cacheKey);
            }

            // Fetch from Apple's JWKS endpoint
            $response = Http::timeout(10)->get('https://appleid.apple.com/auth/keys');
            
            if (!$response->successful()) {
                Log::error('Failed to fetch Apple public keys', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $keys = $response->json();
            
            if (!isset($keys['keys']) || !is_array($keys['keys'])) {
                Log::error('Invalid Apple public keys response', ['response' => $keys]);
                return null;
            }

            // Cache the keys
            cache([$cacheKey => $keys], $cacheTtl);

            Log::info('Apple public keys fetched successfully', [
                'key_count' => count($keys['keys'])
            ]);

            return $keys;

        } catch (\Exception $e) {
            Log::error('Error fetching Apple public keys', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Decode JWT header without verification.
     */
    private function decodeJWTHeader(string $jwt): ?array
    {
        try {
            $parts = explode('.', $jwt);
            if (count($parts) !== 3) {
                return null;
            }

            $header = json_decode(
                base64_decode(
                    str_pad(
                        strtr($parts[0], '-_', '+/'),
                        strlen($parts[0]) % 4,
                        '=',
                        STR_PAD_RIGHT
                    )
                ),
                true
            );

            return $header ?: null;
        } catch (\Exception $e) {
            Log::error('Error decoding JWT header', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Validate Apple token claims.
     */
    private function validateAppleTokenClaims(array $payload): array
    {
        // Check issuer
        if (!isset($payload['iss']) || $payload['iss'] !== 'https://appleid.apple.com') {
            return ['valid' => false, 'reason' => 'Invalid issuer'];
        }

        // Check audience
        $expectedAudience = config('services.apple.client_id');
        if (!isset($payload['aud']) || $payload['aud'] !== $expectedAudience) {
            return ['valid' => false, 'reason' => 'Invalid audience'];
        }

        // Check expiration
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return ['valid' => false, 'reason' => 'Token expired'];
        }

        // Check issued at time (not too far in the future)
        if (!isset($payload['iat']) || $payload['iat'] > (time() + 60)) {
            return ['valid' => false, 'reason' => 'Invalid issued at time'];
        }

        // Check subject
        if (!isset($payload['sub']) || empty($payload['sub'])) {
            return ['valid' => false, 'reason' => 'Missing subject'];
        }

        return ['valid' => true];
    }

    /**
     * Extract user name from Apple user data.
     */
    private function extractUserNameFromAppleData(?array $userData): string
    {
        if (!$userData || !isset($userData['name'])) {
            return 'Apple User';
        }

        $firstName = $userData['name']['firstName'] ?? '';
        $lastName = $userData['name']['lastName'] ?? '';
        
        $fullName = trim($firstName . ' ' . $lastName);
        
        return $fullName ?: 'Apple User';
    }
} 