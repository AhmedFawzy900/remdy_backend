<?php

namespace App\Http\Controllers;

use App\Models\Remedy;
use App\Http\Resources\RemedyResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RemedyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Remedy::with(['remedyType', 'bodySystem']);

            // Filter by title/name
            if ($request->has('title') && $request->title) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            // Filter by disease
            if ($request->has('disease') && $request->disease) {
                $query->where('disease', 'like', '%' . $request->disease . '%');
            }

            // Filter by body system
            if ($request->has('body_system_id') && $request->body_system_id) {
                $query->where('body_system_id', $request->body_system_id);
            }

            // Filter by remedy type
            if ($request->has('remedy_type_id') && $request->remedy_type_id) {
                $query->where('remedy_type_id', $request->remedy_type_id);
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by visibility to plan
            if ($request->has('visible_to_plan')) {
                $query->where('visible_to_plan', $request->visible_to_plan);
            }

            // Search in description
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('disease', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['title', 'disease', 'status', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Limit max per page to 100
            
            $remedies = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => RemedyResource::collection($remedies),
                'pagination' => [
                    'current_page' => $remedies->currentPage(),
                    'last_page' => $remedies->lastPage(),
                    'per_page' => $remedies->perPage(),
                    'total' => $remedies->total(),
                    'from' => $remedies->firstItem(),
                    'to' => $remedies->lastItem(),
                    'has_more_pages' => $remedies->hasMorePages(),
                ],
                'message' => 'Remedies retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve remedies',
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
                'main_image_url' => 'nullable|url',
                'disease' => 'required|string|max:255',
                'remedy_type_id' => 'required|exists:remedy_types,id',
                'body_system_id' => 'required|exists:body_systems,id',
                'description' => 'required|string',
                'visible_to_plan' => 'required|string|in:all,skilled,master,rookie',
                'status' => 'sometimes|in:active,inactive',
                'ingredients' => 'nullable|array',
                'ingredients.*.image_url' => 'nullable|url',
                'ingredients.*.name' => 'required|string',
                'instructions' => 'nullable|array',
                'instructions.*.image_url' => 'nullable|url',
                'instructions.*.name' => 'required|string',
                'benefits' => 'nullable|array',
                'benefits.*.image_url' => 'nullable|url',
                'benefits.*.name' => 'required|string',
                'precautions' => 'nullable|array',
                'precautions.*.image_url' => 'nullable|url',
                'precautions.*.name' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $remedy = Remedy::create([
                'title' => $request->title,
                'main_image_url' => $request->main_image_url,
                'disease' => $request->disease,
                'remedy_type_id' => $request->remedy_type_id,
                'body_system_id' => $request->body_system_id,
                'description' => $request->description,
                'visible_to_plan' => $request->visible_to_plan,
                'status' => $request->status ?? Remedy::STATUS_ACTIVE,
                'ingredients' => $request->ingredients,
                'instructions' => $request->instructions,
                'benefits' => $request->benefits,
                'precautions' => $request->precautions,
            ]);

            $remedy->load(['remedyType', 'bodySystem']);

            return response()->json([
                'success' => true,
                'data' => new RemedyResource($remedy),
                'message' => 'Remedy created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create remedy',
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
            $remedy = Remedy::with(['remedyType', 'bodySystem'])->find($id);
            
            if (!$remedy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new RemedyResource($remedy),
                'message' => 'Remedy retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve remedy',
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
            $remedy = Remedy::find($id);
            
            if (!$remedy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'main_image_url' => 'nullable|url',
                'disease' => 'sometimes|required|string|max:255',
                'remedy_type_id' => 'sometimes|required|exists:remedy_types,id',
                'body_system_id' => 'sometimes|required|exists:body_systems,id',
                'description' => 'sometimes|required|string',
                'visible_to_plan' => 'sometimes|required|string|in:all,skilled,master,rookie',
                'status' => 'sometimes|in:active,inactive',
                'ingredients' => 'nullable|array',
                'ingredients.*.image_url' => 'nullable|url',
                'ingredients.*.name' => 'required|string',
                'instructions' => 'nullable|array',
                'instructions.*.image_url' => 'nullable|url',
                'instructions.*.name' => 'required|string',
                'benefits' => 'nullable|array',
                'benefits.*.image_url' => 'nullable|url',
                'benefits.*.name' => 'required|string',
                'precautions' => 'nullable|array',
                'precautions.*.image_url' => 'nullable|url',
                'precautions.*.name' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $remedy->update($request->all());
            $remedy->load(['remedyType', 'bodySystem']);

            return response()->json([
                'success' => true,
                'data' => new RemedyResource($remedy),
                'message' => 'Remedy updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update remedy',
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
            $remedy = Remedy::find($id);
            
            if (!$remedy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy not found'
                ], 404);
            }

            $remedy->delete();

            return response()->json([
                'success' => true,
                'message' => 'Remedy deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete remedy',
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
            $remedy = Remedy::find($id);
            
            if (!$remedy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy not found'
                ], 404);
            }

            $newStatus = $remedy->status === Remedy::STATUS_ACTIVE 
                ? Remedy::STATUS_INACTIVE 
                : Remedy::STATUS_ACTIVE;

            $remedy->update(['status' => $newStatus]);
            $remedy->load(['remedyType', 'bodySystem']);

            return response()->json([
                'success' => true,
                'data' => new RemedyResource($remedy),
                'message' => 'Remedy status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle remedy status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured remedies for mobile app.
     */
    public function featured(): JsonResponse
    {
        try {
            $remedies = Remedy::with(['remedyType', 'bodySystem'])
                ->where('status', 'active')
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => RemedyResource::collection($remedies),
                'message' => 'Featured remedies retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured remedies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get remedies by body system for mobile app.
     */
    public function byBodySystem(string $bodySystemId): JsonResponse
    {
        try {
            $remedies = Remedy::with(['remedyType', 'bodySystem'])
                ->where('status', 'active')
                ->where('body_system_id', $bodySystemId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => RemedyResource::collection($remedies),
                'message' => 'Remedies by body system retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve remedies by body system',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
