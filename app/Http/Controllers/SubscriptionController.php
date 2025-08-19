<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Http\Resources\SubscriptionResource;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Optional filters
        if ($request->filled('plan')) {
            $query->where('subscription_plan', $request->plan);
        }
        if ($request->filled('status')) {
            $query->where('account_status', $request->status);
        }
        if ($request->filled('active')) {
            $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
            if ($active) {
                $query->where(function ($q) {
                    $q->where('subscription_ends_at', '>', now())
                      ->orWhere('trial_ends_at', '>', now());
                });
            } else {
                $query->where(function ($q) {
                    $q->whereNull('subscription_ends_at')
                      ->orWhere('subscription_ends_at', '<=', now());
                });
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortOrder = $request->get('sort_order', 'desc');
        if (in_array($sortBy, ['full_name', 'email', 'subscription_plan', 'subscription_ends_at', 'updated_at'], true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 15), 100);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($users),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'has_more_pages' => $users->hasMorePages(),
            ],
            'message' => 'Subscriptions retrieved successfully'
        ]);
    }
}
