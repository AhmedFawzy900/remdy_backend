<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Http\Resources\VideoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Video::query();

            // Filter by title
            if ($request->has('title') && $request->title) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by visiblePlans
            if ($request->has('visiblePlans') && $request->visiblePlans) {
                $query->where('visiblePlans', $request->visiblePlans);
            }

            // General search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
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
            $videos = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => VideoResource::collection($videos),
                'pagination' => [
                    'current_page' => $videos->currentPage(),
                    'last_page' => $videos->lastPage(),
                    'per_page' => $videos->perPage(),
                    'total' => $videos->total(),
                    'from' => $videos->firstItem(),
                    'to' => $videos->lastItem(),
                    'has_more_pages' => $videos->hasMorePages(),
                ],
                'message' => 'Videos retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve videos',
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
                'image' => 'nullable|url',
                'videoLink' => 'required|url',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'visiblePlans' => 'required|string',
                'status' => 'sometimes|in:active,inactive',
                'ingredients' => 'nullable|array',
                'ingredients.*.id' => 'nullable|integer',
                'ingredients.*.name' => 'required|string',
                'ingredients.*.image' => 'nullable|url',
                'instructions' => 'nullable|array',
                'instructions.*.id' => 'nullable|integer',
                'instructions.*.title' => 'required|string',
                'instructions.*.image' => 'nullable|url',
                'benefits' => 'nullable|array',
                'benefits.*.id' => 'nullable|integer',
                'benefits.*.title' => 'required|string',
                'benefits.*.image' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $video = Video::create($request->all());

            return response()->json([
                'success' => true,
                'data' => new VideoResource($video),
                'message' => 'Video created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create video',
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
            $video = Video::find($id);
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new VideoResource($video),
                'message' => 'Video retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve video',
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
            $video = Video::find($id);
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }
            $validator = Validator::make($request->all(), [
                'image' => 'nullable|url',
                'videoLink' => 'sometimes|required|url',
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'visiblePlans' => 'sometimes|required|string',
                'status' => 'sometimes|in:active,inactive',
                'ingredients' => 'nullable|array',
                'ingredients.*.id' => 'nullable|integer',
                'ingredients.*.name' => 'required|string',
                'ingredients.*.image' => 'nullable|url',
                'instructions' => 'nullable|array',
                'instructions.*.id' => 'nullable|integer',
                'instructions.*.title' => 'required|string',
                'instructions.*.image' => 'nullable|url',
                'benefits' => 'nullable|array',
                'benefits.*.id' => 'nullable|integer',
                'benefits.*.title' => 'required|string',
                'benefits.*.image' => 'nullable|url',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $video->update($request->all());
            return response()->json([
                'success' => true,
                'data' => new VideoResource($video),
                'message' => 'Video updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update video',
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
            $video = Video::find($id);
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }
            $video->delete();
            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete video',
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
            $video = Video::find($id);
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }
            $newStatus = $video->status === Video::STATUS_ACTIVE 
                ? Video::STATUS_INACTIVE 
                : Video::STATUS_ACTIVE;
            $video->update(['status' => $newStatus]);
            return response()->json([
                'success' => true,
                'data' => new VideoResource($video),
                'message' => 'Video status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle video status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured videos for mobile app.
     */
    public function featured(): JsonResponse
    {
        try {
            $videos = Video::where('status', 'active')
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => VideoResource::collection($videos),
                'message' => 'Featured videos retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured videos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get latest videos for mobile app.
     */
    public function latest(): JsonResponse
    {
        try {
            $videos = Video::where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => VideoResource::collection($videos),
                'message' => 'Latest videos retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve latest videos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
