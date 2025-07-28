<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Remedy;
use App\Models\Article;
use App\Models\Course;
use App\Models\Video;
use App\Http\Resources\RemedyResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\VideoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Add item to favorites.
     */
    public function addToFavorites(Request $request): JsonResponse
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'item_id' => 'required|integer',
                'type' => 'required|string|in:remedy,article,course,video',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $itemId = $request->item_id;
            $type = $request->type;

            // Map type to model class
            $modelMap = [
                'remedy' => Remedy::class,
                'article' => Article::class,
                'course' => Course::class,
                'video' => Video::class,
            ];

            $modelClass = $modelMap[$type];
            $item = $modelClass::find($itemId);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => ucfirst($type) . ' not found'
                ], 404);
            }

            // Check if already favorited
            $existingFavorite = Favorite::where('user_id', $user->id)
                ->where('favoritable_type', $modelClass)
                ->where('favoritable_id', $itemId)
                ->first();

            if ($existingFavorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item is already in your favorites'
                ], 409);
            }

            // Add to favorites
            Favorite::create([
                'user_id' => $user->id,
                'favoritable_type' => $modelClass,
                'favoritable_id' => $itemId,
            ]);

            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' added to favorites successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add to favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from favorites.
     */
    public function removeFromFavorites(Request $request): JsonResponse
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'item_id' => 'required|integer',
                'type' => 'required|string|in:remedy,article,course,video',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $itemId = $request->item_id;
            $type = $request->type;

            // Map type to model class
            $modelMap = [
                'remedy' => Remedy::class,
                'article' => Article::class,
                'course' => Course::class,
                'video' => Video::class,
            ];

            $modelClass = $modelMap[$type];

            // Find and delete the favorite
            $favorite = Favorite::where('user_id', $user->id)
                ->where('favoritable_type', $modelClass)
                ->where('favoritable_id', $itemId)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item is not in your favorites'
                ], 404);
            }

            $favorite->delete();

            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' removed from favorites successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove from favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's favorites by type.
     */
    public function getFavorites(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $type = $request->get('type', 'all'); // all, remedy, article, course, video

            $favorites = [];

            if ($type === 'all' || $type === 'remedy') {
                $remedyFavorites = $user->favoriteRemedies()->with(['remedyType', 'bodySystem'])->get();
                $favorites['remedies'] = RemedyResource::collection($remedyFavorites);
            }

            if ($type === 'all' || $type === 'article') {
                $articleFavorites = $user->favoriteArticles()->get();
                $favorites['articles'] = ArticleResource::collection($articleFavorites);
            }

            if ($type === 'all' || $type === 'course') {
                $courseFavorites = $user->favoriteCourses()->get();
                $favorites['courses'] = CourseResource::collection($courseFavorites);
            }

            if ($type === 'all' || $type === 'video') {
                $videoFavorites = $user->favoriteVideos()->get();
                $favorites['videos'] = VideoResource::collection($videoFavorites);
            }

            return response()->json([
                'success' => true,
                'data' => $favorites,
                'message' => 'Favorites retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if item is favorited.
     */
    public function checkFavorite(Request $request): JsonResponse
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'item_id' => 'required|integer',
                'type' => 'required|string|in:remedy,article,course,video',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $itemId = $request->item_id;
            $type = $request->type;

            // Map type to model class
            $modelMap = [
                'remedy' => Remedy::class,
                'article' => Article::class,
                'course' => Course::class,
                'video' => Video::class,
            ];

            $modelClass = $modelMap[$type];

            $isFavorited = Favorite::where('user_id', $user->id)
                ->where('favoritable_type', $modelClass)
                ->where('favoritable_id', $itemId)
                ->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'is_favorited' => $isFavorited
                ],
                'message' => 'Favorite status checked successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check favorite status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 