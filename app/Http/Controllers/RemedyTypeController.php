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
    public function index(Request $request): JsonResponse
    {
        try {
            $query = RemedyType::query();

            // Filter by name
            if ($request->has('name') && $request->name) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // General search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['name', 'status', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Limit max per page to 100
            
            $remedyTypes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => RemedyTypeResource::collection($remedyTypes),
                'pagination' => [
                    'current_page' => $remedyTypes->currentPage(),
                    'last_page' => $remedyTypes->lastPage(),
                    'per_page' => $remedyTypes->perPage(),
                    'total' => $remedyTypes->total(),
                    'from' => $remedyTypes->firstItem(),
                    'to' => $remedyTypes->lastItem(),
                    'has_more_pages' => $remedyTypes->hasMorePages(),
                ],
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
                'image' => 'nullable|url',
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
                'image' => $request->image,
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
                'image' => 'nullable|url',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $remedyType->update($request->only(['name', 'description', 'image', 'status']));

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
