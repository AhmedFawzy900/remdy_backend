<?php

namespace App\Http\Controllers;

use App\Models\BodySystem;
use App\Http\Resources\BodySystemResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BodySystemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $query = BodySystem::query();
            if (request()->has('title') && request('title')) {
                $query->where('title', 'like', '%' . request('title') . '%');
            }
            $bodySystems = $query->get();
            return response()->json([
                'success' => true,
                'data' => BodySystemResource::collection($bodySystems),
                'message' => 'Body systems retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve body systems',
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
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $bodySystem = BodySystem::create([
                'title' => $request->title,
                'description' => $request->description,
                'status' => $request->status ?? BodySystem::STATUS_ACTIVE,
            ]);

            return response()->json([
                'success' => true,
                'data' => new BodySystemResource($bodySystem),
                'message' => 'Body system created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create body system',
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
            $bodySystem = BodySystem::find($id);
            
            if (!$bodySystem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Body system not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new BodySystemResource($bodySystem),
                'message' => 'Body system retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve body system',
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
            $bodySystem = BodySystem::find($id);
            
            if (!$bodySystem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Body system not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $bodySystem->update($request->only(['title', 'description', 'status']));

            return response()->json([
                'success' => true,
                'data' => new BodySystemResource($bodySystem),
                'message' => 'Body system updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update body system',
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
            $bodySystem = BodySystem::find($id);
            
            if (!$bodySystem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Body system not found'
                ], 404);
            }

            $bodySystem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Body system deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete body system',
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
            $bodySystem = BodySystem::find($id);
            
            if (!$bodySystem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Body system not found'
                ], 404);
            }

            $newStatus = $bodySystem->status === BodySystem::STATUS_ACTIVE 
                ? BodySystem::STATUS_INACTIVE 
                : BodySystem::STATUS_ACTIVE;

            $bodySystem->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'data' => new BodySystemResource($bodySystem),
                'message' => 'Body system status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle body system status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
