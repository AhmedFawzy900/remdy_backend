<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Review::with('user');

            // Filter by type
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Filter by element_id
            if ($request->has('element_id') && $request->element_id) {
                $query->where('element_id', $request->element_id);
            }

            // Filter by user_id
            if ($request->has('user_id') && $request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // General search in message
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('message', 'like', '%' . $search . '%');
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            if (in_array($sortBy, ['rate', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100);
            $reviews = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ReviewResource::collection($reviews),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'from' => $reviews->firstItem(),
                    'to' => $reviews->lastItem(),
                    'has_more_pages' => $reviews->hasMorePages(),
                ],
                'message' => 'Reviews retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews',
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
                'type' => 'required|in:remedy,course,video',
                'element_id' => 'required|integer',
                'rate' => 'required|integer|min:1|max:5',
                'message' => 'required|string',
                'status' => 'sometimes|in:pending,accepted,rejected',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $review = Review::create([
                'user_id' => auth()->user()->id,
                'type' => $request->type,
                'element_id' => $request->element_id,
                'rate' => $request->rate,
                'message' => $request->message,
                'status' => $request->status ?? Review::STATUS_PENDING,
            ]);
            $review->load('user');

            return response()->json([
                'success' => true,
                'data' => new ReviewResource($review),
                'message' => 'Review created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create review',
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
            $review = Review::with('user')->find($id);
            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new ReviewResource($review),
                'message' => 'Review retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve review',
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
            $review = Review::find($id);
            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|in:pending,accepted,rejected',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $review->update($request->all());
            $review->load('user');

            return response()->json([
                'success' => true,
                'data' => new ReviewResource($review),
                'message' => 'Review updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review',
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
            $review = Review::find($id);
            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found'
                ], 404);
            }
            $review->delete();
            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review',
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
            $review = Review::find($id);
            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found'
                ], 404);
            }
            // Cycle status: pending -> accepted -> rejected -> pending
            $newStatus = match($review->status) {
                Review::STATUS_PENDING => Review::STATUS_ACCEPTED,
                Review::STATUS_ACCEPTED => Review::STATUS_REJECTED,
                Review::STATUS_REJECTED => Review::STATUS_PENDING,
                default => Review::STATUS_PENDING,
            };
            $review->update(['status' => $newStatus]);
            $review->load('user');
            return response()->json([
                'success' => true,
                'data' => new ReviewResource($review),
                'message' => 'Review status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle review status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 