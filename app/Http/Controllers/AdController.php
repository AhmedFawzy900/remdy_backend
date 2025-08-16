<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Http\Resources\AdResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Ad::query();

            // Filter by title
            if ($request->has('title') && $request->title) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by placement type
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Filter by element id
            if ($request->has('element_id') && $request->element_id !== null) {
                $query->where('element_id', $request->element_id);
            }

            // General search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('title', 'like', '%' . $search . '%');
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['title', 'status', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Limit max per page to 100
            
            $ads = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => AdResource::collection($ads),
                'pagination' => [
                    'current_page' => $ads->currentPage(),
                    'last_page' => $ads->lastPage(),
                    'per_page' => $ads->perPage(),
                    'total' => $ads->total(),
                    'from' => $ads->firstItem(),
                    'to' => $ads->lastItem(),
                    'has_more_pages' => $ads->hasMorePages(),
                ],
                'message' => 'Ads retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'image' => 'nullable|url',
                'url' => 'nullable|url',
                'status' => 'sometimes|in:active,inactive',
                'type' => 'required|in:home,remedy,video,course',
                'element_id' => 'required_unless:type,home|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $ad = Ad::create([
                'title' => $request->title,
                'image' => $request->image,
                'url' => $request->url,
                'status' => $request->status ?? Ad::STATUS_ACTIVE,
                'type' => $request->type,
                'element_id' => $request->type === Ad::TYPE_HOME ? null : $request->element_id,
            ]);

            return response()->json([
                'success' => true,
                'data' => new AdResource($ad),
                'message' => 'Ad created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ad',
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
            $ad = Ad::find($id);
            
            if (!$ad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ad not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new AdResource($ad),
                'message' => 'Ad retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $ad = Ad::find($id);
            
            if (!$ad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ad not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'image' => 'nullable|url',
                'url' => 'nullable|url',
                'status' => 'sometimes|in:active,inactive',
                'type' => 'sometimes|in:home,remedy,video,course',
                'element_id' => 'sometimes|required_unless:type,home|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $ad->update($request->all());

            return response()->json([
                'success' => true,
                'data' => new AdResource($ad),
                'message' => 'Ad updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ad',
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
            $ad = Ad::find($id);
            
            if (!$ad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ad not found'
                ], 404);
            }

            $ad->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ad deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ad',
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
            $ad = Ad::find($id);
            
            if (!$ad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ad not found'
                ], 404);
            }

            $newStatus = $ad->status === Ad::STATUS_ACTIVE 
                ? Ad::STATUS_INACTIVE 
                : Ad::STATUS_ACTIVE;

            $ad->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'data' => new AdResource($ad),
                'message' => 'Ad status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle ad status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active ads for mobile app.
     */
    public function active(): JsonResponse
    {
        try {
            $type = request()->get('type');
            $elementId = request()->get('element_id');

            $ads = Ad::active()
                ->when($type, fn($q) => $q->where('type', $type))
                ->when($elementId !== null, fn($q) => $q->where('element_id', $elementId))
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => AdResource::collection($ads),
                'message' => 'Active ads retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active ads',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
