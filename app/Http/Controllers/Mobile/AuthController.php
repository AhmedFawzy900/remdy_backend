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
            $user->code_usage !== 'done' && 
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

            return response()->json([
                'success' => true,
                'data' => $user,
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
                'password' => 'sometimes|string|min:6',
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
            
            if ($request->password) {
                $input['password'] = Hash::make($request->password);
            }

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
     * Check if email exists.
     */
    private function checkEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
} 