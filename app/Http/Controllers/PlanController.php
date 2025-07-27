<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Http\Resources\PlanResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Plan::query();
            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            // Sort by order (default), or other fields
            $sortBy = $request->get('sort_by', 'order');
            $sortOrder = $request->get('sort_order', 'asc');
            if (in_array($sortBy, ['order', 'name', 'price_monthly', 'price_yearly', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }
            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100);
            $plans = $query->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => PlanResource::collection($plans),
                'pagination' => [
                    'current_page' => $plans->currentPage(),
                    'last_page' => $plans->lastPage(),
                    'per_page' => $plans->perPage(),
                    'total' => $plans->total(),
                    'from' => $plans->firstItem(),
                    'to' => $plans->lastItem(),
                    'has_more_pages' => $plans->hasMorePages(),
                ],
                'message' => 'Plans retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve plans',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $plan = Plan::find($id);
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new PlanResource($plan),
                'message' => 'Plan retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 