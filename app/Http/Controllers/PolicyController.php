<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Http\Resources\PolicyResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Policy::query();

            // Filter by type
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // General search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('content', 'like', '%' . $search . '%');
                });
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            if (in_array($sortBy, ['type', 'status', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100);
            $policies = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => PolicyResource::collection($policies),
                'pagination' => [
                    'current_page' => $policies->currentPage(),
                    'last_page' => $policies->lastPage(),
                    'per_page' => $policies->perPage(),
                    'total' => $policies->total(),
                    'from' => $policies->firstItem(),
                    'to' => $policies->lastItem(),
                    'has_more_pages' => $policies->hasMorePages(),
                ],
                'message' => 'Policies retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve policies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:privacy,terms',
                'content' => 'required|string',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $policy = Policy::create([
                'type' => $request->type,
                'content' => $request->content,
                'status' => $request->status ?? Policy::STATUS_ACTIVE,
            ]);

            return response()->json([
                'success' => true,
                'data' => new PolicyResource($policy),
                'message' => 'Policy created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create policy',
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
            $policy = Policy::find($id);
            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new PolicyResource($policy),
                'message' => 'Policy retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve policy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $policy = Policy::find($id);
            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|required|in:privacy,terms',
                'content' => 'sometimes|required|string',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $policy->update($request->all());

            return response()->json([
                'success' => true,
                'data' => new PolicyResource($policy),
                'message' => 'Policy updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update policy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $policy = Policy::find($id);
            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy not found'
                ], 404);
            }
            $policy->delete();
            return response()->json([
                'success' => true,
                'message' => 'Policy deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete policy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $policy = Policy::find($id);
            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy not found'
                ], 404);
            }
            $newStatus = $policy->status === Policy::STATUS_ACTIVE 
                ? Policy::STATUS_INACTIVE 
                : Policy::STATUS_ACTIVE;
            $policy->update(['status' => $newStatus]);
            return response()->json([
                'success' => true,
                'data' => new PolicyResource($policy),
                'message' => 'Policy status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle policy status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 