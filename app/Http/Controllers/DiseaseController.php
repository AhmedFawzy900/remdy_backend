<?php

namespace App\Http\Controllers;

use App\Models\Disease;
use App\Http\Resources\DiseaseResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DiseaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Disease::query();

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
            $diseases = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => DiseaseResource::collection($diseases),
                'pagination' => [
                    'current_page' => $diseases->currentPage(),
                    'last_page' => $diseases->lastPage(),
                    'per_page' => $diseases->perPage(),
                    'total' => $diseases->total(),
                    'from' => $diseases->firstItem(),
                    'to' => $diseases->lastItem(),
                    'has_more_pages' => $diseases->hasMorePages(),
                ],
                'message' => 'Diseases retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve diseases',
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
                'image' => 'nullable|url',
                'description' => 'required|string',
                'status' => 'sometimes|in:active,inactive',
                'symptoms' => 'nullable|array',
                'symptoms.*' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $disease = Disease::create($request->all());

            return response()->json([
                'success' => true,
                'data' => new DiseaseResource($disease),
                'message' => 'Disease created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create disease',
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
            $disease = Disease::find($id);
            if (!$disease) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disease not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new DiseaseResource($disease),
                'message' => 'Disease retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve disease',
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
            $disease = Disease::find($id);
            if (!$disease) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disease not found'
                ], 404);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'image' => 'nullable|url',
                'description' => 'sometimes|required|string',
                'status' => 'sometimes|in:active,inactive',
                'symptoms' => 'nullable|array',
                'symptoms.*' => 'string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $disease->update($request->all());
            return response()->json([
                'success' => true,
                'data' => new DiseaseResource($disease),
                'message' => 'Disease updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update disease',
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
            $disease = Disease::find($id);
            if (!$disease) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disease not found'
                ], 404);
            }
            $disease->delete();
            return response()->json([
                'success' => true,
                'message' => 'Disease deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete disease',
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
            $disease = Disease::find($id);
            if (!$disease) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disease not found'
                ], 404);
            }
            $newStatus = $disease->status === Disease::STATUS_ACTIVE 
                ? Disease::STATUS_INACTIVE 
                : Disease::STATUS_ACTIVE;
            $disease->update(['status' => $newStatus]);
            return response()->json([
                'success' => true,
                'data' => new DiseaseResource($disease),
                'message' => 'Disease status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle disease status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
