<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SubscriptionHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $user->subscription_plan,
                'interval' => $user->subscription_interval,
                'started_at' => $user->subscription_started_at,
                'ends_at' => $user->subscription_ends_at,
                'trial_ends_at' => $user->trial_ends_at,
                'has_used_trial' => (bool) $user->has_used_trial,
                'is_active' => $user->subscription_ends_at && now()->lt($user->subscription_ends_at),
            ],
        ]);
    }

    public function activate(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        $validator = Validator::make($request->all(), [
            'plan' => 'nullable|in:rookie,skilled,master',
            'plan_slug' => 'nullable|in:rookie,skilled,master',
            'interval' => 'nullable|in:monthly,yearly',
            'reference' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $planValue = $request->get('plan', $request->get('plan_slug'));
        if (!$planValue) {
            return response()->json([
                'success' => false,
                'message' => 'Plan is required',
            ], 422);
        }

        // Prevent master users from downgrading to skilled
        if ($user->subscription_plan === 'master' && $planValue === 'skilled') {
            return response()->json([
                'success' => false,
                'message' => 'Master plan users cannot downgrade to skilled plan',
            ], 422);
        }

        // Mark any existing active subscriptions as cancelled
        SubscriptionHistory::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $amountPaid = 0; // Initialize amount paid

        if (in_array($planValue, ['skilled', 'master'], true)) {
            if (!$request->interval) {
                return response()->json([
                    'success' => false,
                    'message' => 'Interval is required for paid plans',
                ], 422);
            }
            $start = now();
            $end = $request->interval === 'monthly' ? $start->copy()->addMonth() : $start->copy()->addYear();
            
            // Determine the amount paid - use amount_paid if provided, otherwise use price, otherwise use default pricing
            $amountPaid = $request->amount_paid ?? $request->price ?? $this->getDefaultPrice($planValue, $request->interval);

            // Create subscription history record
            $subscriptionHistory = SubscriptionHistory::create([
                'user_id' => $user->id,
                'plan' => $planValue,
                'interval' => $request->interval,
                'started_at' => $start,
                'ends_at' => $end,
                'reference' => $request->reference,
                'status' => 'active',
                'action' => 'activated',
                'amount_paid' => $amountPaid,
                'payment_method' => $request->payment_method ?? null,
                'notes' => $request->notes ?? null,
            ]);

            // Update user table for backward compatibility (optional)
            $user->subscription_plan = $planValue;
            $user->subscription_interval = $request->interval;
            $user->subscription_started_at = $start;
            $user->subscription_ends_at = $end;
            $user->last_subscription_reference = $request->reference;
            $user->save();
        } else {
            // rookie plan: create history record for downgrade
            $amountPaid = 0; // Rookie plan is free
            
            SubscriptionHistory::create([
                'user_id' => $user->id,
                'plan' => 'rookie',
                'interval' => null,
                'started_at' => now(),
                'ends_at' => null,
                'reference' => $request->reference,
                'status' => 'active',
                'action' => 'activated',
                'amount_paid' => $amountPaid,
                'payment_method' => null,
                'notes' => 'Downgraded to rookie plan',
            ]);

            // Update user table for backward compatibility
            $user->subscription_plan = 'rookie';
            $user->subscription_interval = null;
            $user->subscription_started_at = null;
            $user->subscription_ends_at = null;
            $user->last_subscription_reference = $request->reference;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription activated',
            'data' => [
                'plan' => $user->subscription_plan,
                'interval' => $user->subscription_interval,
                'started_at' => $user->subscription_started_at,
                'ends_at' => $user->subscription_ends_at,
                'trial_ends_at' => $user->trial_ends_at,
                'has_used_trial' => (bool) $user->has_used_trial,
                'price' => $amountPaid,
                'amount_paid' => $amountPaid,
            ],
        ], 200);
    }

    public function cancel(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        
        // Mark any existing active subscriptions as cancelled
        SubscriptionHistory::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        // Create new rookie subscription history record
        SubscriptionHistory::create([
            'user_id' => $user->id,
            'plan' => 'rookie',
            'interval' => null,
            'started_at' => now(),
            'ends_at' => null,
            'reference' => null,
            'status' => 'active',
            'action' => 'cancelled',
            'amount_paid' => 0,
            'payment_method' => null,
            'notes' => 'Subscription cancelled and downgraded to rookie',
        ]);

        // Update user table for backward compatibility
        $user->subscription_plan = 'rookie';
        $user->subscription_interval = null;
        $user->subscription_started_at = null;
        $user->subscription_ends_at = null;
        $user->last_subscription_reference = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Subscription canceled and downgraded to rookie',
            'data' => [
                'plan' => $user->subscription_plan,
                'interval' => $user->subscription_interval,
                'ends_at' => $user->subscription_ends_at,
            ],
        ], 200);
    }

    public function purchaseHistory(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        
        // Get subscription history from database
        $subscriptionPlans = $user->subscriptionHistories()
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'type' => 'subscription',
                    'plan' => $subscription->plan,
                    'interval' => $subscription->interval,
                    'started_at' => $subscription->started_at,
                    'ends_at' => $subscription->ends_at,
                    'reference' => $subscription->reference,
                    'status' => $subscription->status,
                    'action' => $subscription->action,
                    'price' => $subscription->amount_paid, // Using amount_paid as the price
                    'amount_paid' => $subscription->amount_paid,
                    'payment_method' => $subscription->payment_method,
                    'notes' => $subscription->notes,
                    'is_active' => $subscription->isActive(),
                    'created_at' => $subscription->created_at,
                ];
            });

        // Get current active subscription
        $currentSubscription = $subscriptionPlans->where('is_active', true)->first();

        // Get course purchases
        $coursePurchases = $user->coursePurchases()
            ->with('course:id,title,price,image')
            ->orderBy('purchased_at', 'desc')
            ->get()
            ->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'title' => $purchase->course->title,
                    'amount_paid' => $purchase->amount_paid,
                    'payment_method' => $purchase->payment_method,
                    'status' => $purchase->status,
                    'purchased_at' => $purchase->purchased_at,
                    'reference' => $purchase->payment_token,
                    'course_image' => $purchase->course->image,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'subscription_plans' => $subscriptionPlans,
                'course_purchases' => $coursePurchases,
                'total_subscription_plans' => $subscriptionPlans->count(),
                'total_course_purchases' => $coursePurchases->count(),
                'current_subscription' => $currentSubscription,
            ],
        ]);
    }

    /**
     * Get default pricing for subscription plans
     */
    private function getDefaultPrice(string $plan, string $interval): float
    {
        $pricing = [
            'rookie' => [
                'monthly' => 0,
                'yearly' => 0,
            ],
            'skilled' => [
                'monthly' => 19.99,
                'yearly' => 199.99,
            ],
            'master' => [
                'monthly' => 29.99,
                'yearly' => 299.99,
            ],
        ];

        return $pricing[$plan][$interval] ?? 0;
    }
}


