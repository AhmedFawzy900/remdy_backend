<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SubscriptionHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionWebhookController extends Controller
{
    /**
     * Handle Apple App Store webhook notifications
     */
    public function handleAppleWebhook(Request $request): JsonResponse
    {
        try {
            Log::info('Apple webhook received', ['payload' => $request->all()]);

            // Verify the webhook signature if needed
            // if (!$this->verifyAppleSignature($request)) {
            //     Log::warning('Apple webhook signature verification failed');
            //     return response()->json(['error' => 'Invalid signature'], 401);
            // }

            $payload = $request->all();
            
            // Handle different notification types
            if (isset($payload['notificationType'])) {
                $this->processAppleNotification($payload);
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Apple webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);
            
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle Google Play webhook notifications
     */
    public function handleGoogleWebhook(Request $request): JsonResponse
    {
        try {
            Log::info('Google webhook received', ['payload' => $request->all()]);

            // Verify the webhook signature if needed
            if (!$this->verifyGoogleSignature($request)) {
                Log::warning('Google webhook signature verification failed');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->all();
            
            // Handle different message types
            if (isset($payload['message']['data'])) {
                $this->processGoogleNotification($payload);
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Google webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);
            
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Process Apple App Store notification
     */
    private function processAppleNotification(array $payload): void
    {
        $notificationType = $payload['notificationType'];
        $transactionInfo = $payload['data']['signedTransactionInfo'] ?? null;
        $renewalInfo = $payload['data']['signedRenewalInfo'] ?? null;

        Log::info('Processing Apple notification', [
            'type' => $notificationType,
            'transaction_info' => $transactionInfo,
            'renewal_info' => $renewalInfo
        ]);

        // Decode the signed transaction info (in production, you'd verify JWT signature)
        $transactionData = $this->decodeAppleTransactionInfo($transactionInfo);
        
        if (!$transactionData) {
            Log::warning('Failed to decode Apple transaction info');
            return;
        }

        $originalTransactionId = $transactionData['originalTransactionId'] ?? null;
        $user = $this->findUserByAppleTransactionId($originalTransactionId);

        if (!$user) {
            Log::warning('User not found for Apple transaction', ['transaction_id' => $originalTransactionId]);
            return;
        }

        // Handle different notification types that require downgrade
        $downgradeTriggers = [
            'EXPIRED',
            'DID_FAIL_TO_RENEW',
            'GRACE_PERIOD_EXPIRED',
            'REFUND',
            'REVOKE',
            'DID_CHANGE_RENEWAL_STATUS', // When auto-renewal is turned off
            'SUBSCRIBED' // When subscription ends
        ];

        if (in_array($notificationType, $downgradeTriggers)) {
            $this->downgradeUserToRookie($user, 'apple', $notificationType, $originalTransactionId);
        }
    }

    /**
     * Process Google Play notification
     */
    private function processGoogleNotification(array $payload): void
    {
        $messageData = base64_decode($payload['message']['data']);
        $notification = json_decode($messageData, true);

        if (!$notification) {
            Log::warning('Failed to decode Google notification data');
            return;
        }

        Log::info('Processing Google notification', ['notification' => $notification]);

        $subscriptionNotification = $notification['subscriptionNotification'] ?? null;
        
        if (!$subscriptionNotification) {
            Log::info('Not a subscription notification, skipping');
            return;
        }

        $purchaseToken = $subscriptionNotification['purchaseToken'] ?? null;
        $notificationType = $subscriptionNotification['notificationType'] ?? null;

        $user = $this->findUserByGooglePurchaseToken($purchaseToken);

        if (!$user) {
            Log::warning('User not found for Google purchase token', ['purchase_token' => $purchaseToken]);
            return;
        }

        // Handle different notification types that require downgrade
        // Google Play notification types: https://developer.android.com/google/play/billing/rtdn-reference
        $downgradeTriggers = [
            1,  // SUBSCRIPTION_RECOVERED
            2,  // SUBSCRIPTION_RENEWED
            3,  // SUBSCRIPTION_CANCELED
            4,  // SUBSCRIPTION_PURCHASED
            5,  // SUBSCRIPTION_ON_HOLD
            6,  // SUBSCRIPTION_IN_GRACE_PERIOD
            7,  // SUBSCRIPTION_RESTARTED
            8,  // SUBSCRIPTION_PRICE_CHANGE_CONFIRMED
            9,  // SUBSCRIPTION_DEFERRED
            10, // SUBSCRIPTION_PAUSED
            11, // SUBSCRIPTION_PAUSE_SCHEDULE_CHANGED
            12, // SUBSCRIPTION_REVOKED
            13, // SUBSCRIPTION_EXPIRED
        ];

        // Only downgrade on specific events
        $actualDowngradeTriggers = [3, 5, 12, 13]; // CANCELED, ON_HOLD, REVOKED, EXPIRED

        if (in_array($notificationType, $actualDowngradeTriggers)) {
            $this->downgradeUserToRookie($user, 'google', $notificationType, $purchaseToken);
        }
    }

    /**
     * Downgrade user to rookie plan
     */
    private function downgradeUserToRookie(User $user, string $platform, $notificationType, string $reference): void
    {
        try {
            DB::beginTransaction();

            Log::info('Downgrading user to rookie plan', [
                'user_id' => $user->id,
                'platform' => $platform,
                'notification_type' => $notificationType,
                'reference' => $reference
            ]);

            // Mark any existing active subscriptions as cancelled
            SubscriptionHistory::where('user_id', $user->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'notes' => "Cancelled via {$platform} webhook: {$notificationType}"
                ]);

            // Create new rookie subscription history record
            SubscriptionHistory::create([
                'user_id' => $user->id,
                'plan' => 'rookie',
                'interval' => null,
                'started_at' => now(),
                'ends_at' => null,
                'reference' => $reference,
                'status' => 'active',
                'action' => 'downgraded',
                'amount_paid' => 0,
                'payment_method' => $platform,
                'notes' => "Automatically downgraded to rookie via {$platform} webhook: {$notificationType}",
                'platform' => $platform,
            ]);

            // Update user table for backward compatibility
            $user->update([
                'subscription_plan' => 'rookie',
                'subscription_interval' => null,
                'subscription_started_at' => null,
                'subscription_ends_at' => null,
                'last_subscription_reference' => $reference,
            ]);

            DB::commit();

            Log::info('User successfully downgraded to rookie plan', [
                'user_id' => $user->id,
                'platform' => $platform
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to downgrade user to rookie plan', [
                'user_id' => $user->id,
                'platform' => $platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Find user by Apple original transaction ID
     */
    private function findUserByAppleTransactionId(string $transactionId): ?User
    {
        // Look for user by transaction reference in subscription history
        $subscription = SubscriptionHistory::where('reference', $transactionId)
            ->where('platform', 'ios')
            ->first();

        if ($subscription) {
            return $subscription->user;
        }

        // Fallback: look in user's last_subscription_reference
        return User::where('last_subscription_reference', $transactionId)->first();
    }

    /**
     * Find user by Google purchase token
     */
    private function findUserByGooglePurchaseToken(string $purchaseToken): ?User
    {
        // Look for user by purchase token reference in subscription history
        $subscription = SubscriptionHistory::where('reference', $purchaseToken)
            ->where('platform', 'android')
            ->first();

        if ($subscription) {
            return $subscription->user;
        }

        // Fallback: look in user's last_subscription_reference
        return User::where('last_subscription_reference', $purchaseToken)->first();
    }

    /**
     * Verify Apple webhook signature
     */
    private function verifyAppleSignature(Request $request): bool
    {
        // In production, implement proper JWT signature verification
        // For now, we'll do basic validation
        
        $appleSharedSecret = config('services.apple.shared_secret');
        
        if (!$appleSharedSecret) {
            Log::warning('Apple shared secret not configured, skipping signature verification');
            return true; // Allow in development
        }

        // Implement Apple's signature verification logic here
        // This is a simplified version - in production, verify the JWT signature
        return true;
    }

    /**
     * Verify Google webhook signature
     */
    private function verifyGoogleSignature(Request $request): bool
    {
        // In production, implement proper Pub/Sub message verification
        
        $googleWebhookSecret = config('services.google.webhook_secret');
        
        if (!$googleWebhookSecret) {
            Log::warning('Google webhook secret not configured, skipping signature verification');
            return true; // Allow in development
        }

        // Implement Google's signature verification logic here
        return true;
    }

    /**
     * Decode Apple transaction info (simplified version)
     */
    private function decodeAppleTransactionInfo(?string $signedTransactionInfo): ?array
    {
        if (!$signedTransactionInfo) {
            return null;
        }

        // In production, properly decode and verify the JWT
        // For now, we'll assume it's base64 encoded JSON for testing
        try {
            // This is a simplified version - in production, use proper JWT decoding
            $parts = explode('.', $signedTransactionInfo);
            if (count($parts) >= 2) {
                $payload = base64_decode($parts[1]);
                return json_decode($payload, true);
            }
        } catch (\Exception $e) {
            Log::error('Failed to decode Apple transaction info', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Handle test webhook endpoint for development
     */
    public function handleTestWebhook(Request $request): JsonResponse
    {
        Log::info('Test webhook received', ['payload' => $request->all()]);

        $platform = $request->input('platform', 'test');
        $userId = $request->input('user_id');
        $action = $request->input('action', 'downgrade');

        if (!$userId) {
            return response()->json(['error' => 'user_id is required'], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($action === 'downgrade') {
            $this->downgradeUserToRookie($user, $platform, 'TEST_DOWNGRADE', 'test_reference_' . time());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Test webhook processed successfully',
            'user_plan' => $user->fresh()->subscription_plan
        ]);
    }
}
