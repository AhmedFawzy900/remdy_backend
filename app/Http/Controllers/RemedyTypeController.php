<?php

namespace App\Http\Controllers;

use App\Models\RemedyType;
use App\Http\Resources\RemedyTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RemedyTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $query = RemedyType::query();
            if (request()->has('name') && request('name')) {
                $query->where('name', 'like', '%' . request('name') . '%');
            }
            $remedyTypes = $query->get();
            return response()->json([
                'success' => true,
                'data' => RemedyTypeResource::collection($remedyTypes),
                'message' => 'Remedy types retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve remedy types',
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
                'name' => 'required|string|max:255',
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

            $remedyType = RemedyType::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? RemedyType::STATUS_ACTIVE,
            ]);

            return response()->json([
                'success' => true,
                'data' => new RemedyTypeResource($remedyType),
                'message' => 'Remedy type created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create remedy type',
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
            $remedyType = RemedyType::find($id);
            
            if (!$remedyType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy type not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new RemedyTypeResource($remedyType),
                'message' => 'Remedy type retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve remedy type',
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
            $remedyType = RemedyType::find($id);
            
            if (!$remedyType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy type not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
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

            $remedyType->update($request->only(['name', 'description', 'status']));

            return response()->json([
                'success' => true,
                'data' => new RemedyTypeResource($remedyType),
                'message' => 'Remedy type updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update remedy type',
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
            $remedyType = RemedyType::find($id);
            
            if (!$remedyType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy type not found'
                ], 404);
            }

            $remedyType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Remedy type deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete remedy type',
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
            $remedyType = RemedyType::find($id);
            
            if (!$remedyType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy type not found'
                ], 404);
            }

            $newStatus = $remedyType->status === RemedyType::STATUS_ACTIVE 
                ? RemedyType::STATUS_INACTIVE 
                : RemedyType::STATUS_ACTIVE;

            $remedyType->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'data' => new RemedyTypeResource($remedyType),
                'message' => 'Remedy type status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle remedy type status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
