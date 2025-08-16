<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\ArticleIndexResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Article::with(['reviews.user']);

            // Filter by title
            if ($request->has('title') && $request->title) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by plan
            if ($request->has('plan') && $request->plan) {
                $query->where('plans', $request->plan);
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
            
            $articles = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ArticleIndexResource::collection($articles),
                'pagination' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'from' => $articles->firstItem(),
                    'to' => $articles->lastItem(),
                    'has_more_pages' => $articles->hasMorePages(),
                ],
                'message' => 'Articles retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve articles',
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
                'image' => 'nullable|url',
                'description' => 'required|string',
                'plants' => 'nullable|array',
                'plants.*.image' => 'nullable|url',
                'plants.*.title' => 'required|string',
                'plants.*.description' => 'required|string',
                'plans' => 'nullable',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $article = Article::create([
                'title' => $request->title,
                'image' => $request->image,
                'description' => $request->description,
                'plants' => $request->plants,
                'plans' => $request->plans,
                'status' => $request->status ?? Article::STATUS_ACTIVE,
            ]);

            return response()->json([
                'success' => true,
                'data' => new ArticleResource($article),
                'message' => 'Article created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create article',
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
            $article = Article::with(['reviews.user', 'reviews.reactions'])->find($id);
            
            if (!$article) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new ArticleResource($article),
                'message' => 'Article retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve article',
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
            $article = Article::find($id);
            
            if (!$article) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'image' => 'nullable|url',
                'description' => 'sometimes|required|string',
                'plants' => 'nullable|array',
                'plants.*.image' => 'nullable|url',
                'plants.*.title' => 'required|string',
                'plants.*.description' => 'required|string',
                'plans' => 'nullable',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $article->update($request->all());

            return response()->json([
                'success' => true,
                'data' => new ArticleResource($article),
                'message' => 'Article updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update article',
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
            $article = Article::find($id);
            
            if (!$article) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found'
                ], 404);
            }

            $article->delete();

            return response()->json([
                'success' => true,
                'message' => 'Article deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete article',
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
            $article = Article::find($id);
            
            if (!$article) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found'
                ], 404);
            }

            $newStatus = $article->status === Article::STATUS_ACTIVE 
                ? Article::STATUS_INACTIVE 
                : Article::STATUS_ACTIVE;

            $article->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'data' => new ArticleResource($article),
                'message' => 'Article status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle article status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured articles for mobile app.
     */
    public function featured(): JsonResponse
    {
        try {
            $articles = Article::where('status', 'active')
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => ArticleResource::collection($articles),
                'message' => 'Featured articles retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured articles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get latest articles for mobile app.
     */
    public function latest(): JsonResponse
    {
        try {
            $articles = Article::where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => ArticleResource::collection($articles),
                'message' => 'Latest articles retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve latest articles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
