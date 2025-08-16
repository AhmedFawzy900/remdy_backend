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
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:privacy,terms',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $policy = Policy::where('type', $request->type)->first();

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

            $existingPolicy = Policy::where('type', $request->type)->first();

            if ($existingPolicy) {
                $existingPolicy->update([
                    'content' => $request->content,
                    'status' => $request->status ?? $existingPolicy->status,
                ]);
                $policy = $existingPolicy;
                $statusCode = 200;
                $message = 'Policy updated successfully';
            } else {
                $policy = Policy::create([
                    'type' => $request->type,
                    'content' => $request->content,
                    'status' => $request->status ?? Policy::STATUS_ACTIVE,
                ]);
                $statusCode = 201;
                $message = 'Policy created successfully';
            }

            return response()->json([
                'success' => true,
                'data' => new PolicyResource($policy),
                'message' => $message
            ], $statusCode);
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
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:privacy,terms',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $policy = Policy::where('type', $request->type)->first();
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
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:privacy,terms',
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

            $policy = Policy::where('type', $request->type)->first();

            if ($policy) {
                $policy->update($request->only(['content', 'status']));
                $message = 'Policy updated successfully';
                $statusCode = 200;
            } else {
                if (!$request->has('content')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Content is required to create policy'
                    ], 422);
                }
                $policy = Policy::create([
                    'type' => $request->type,
                    'content' => $request->content,
                    'status' => $request->status ?? Policy::STATUS_ACTIVE,
                ]);
                $message = 'Policy created successfully';
                $statusCode = 201;
            }

            return response()->json([
                'success' => true,
                'data' => new PolicyResource($policy),
                'message' => $message
            ], $statusCode);
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
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:privacy,terms',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $policy = Policy::where('type', $request->type)->first();
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
    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:privacy,terms',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $policy = Policy::where('type', $request->type)->first();
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