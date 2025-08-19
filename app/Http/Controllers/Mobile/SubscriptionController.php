<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        if (in_array($planValue, ['skilled', 'master'], true)) {
            if (!$request->interval) {
                return response()->json([
                    'success' => false,
                    'message' => 'Interval is required for paid plans',
                ], 422);
            }
            $start = now();
            $end = $request->interval === 'monthly' ? $start->copy()->addMonth() : $start->copy()->addYear();
            $user->subscription_plan = $planValue;
            $user->subscription_interval = $request->interval;
            $user->subscription_started_at = $start;
            $user->subscription_ends_at = $end;
            $user->last_subscription_reference = $request->reference;
            $user->save();
        } else {
            // rookie plan: clear subscription dates/interval
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
            ],
        ], 200);
    }

    public function cancel(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
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

    
}


