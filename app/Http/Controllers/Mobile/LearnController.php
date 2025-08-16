<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Remedy;
use App\Models\Article;
use App\Models\Course;
use App\Models\Video;
use App\Http\Resources\RemedyResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CourseIndexResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\RemedyIndexResource;
use App\Http\Resources\VideoIndexResource;
use App\Http\Resources\VideoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LearnController extends Controller
{
    /**
     * Get personalized learning suggestions for mobile app.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get top rated remedies (by average rating)
            $topRemedies = Remedy::with(['remedyType', 'bodySystem', 'reviews.user', 'reviews.reactions','diseaseRelation','remedyTypes','bodySystems','diseases'])
                ->where('status', 'active')
                ->withAvg('reviews', 'rate')
                ->orderBy('reviews_avg_rate', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get top rated courses (by average rating)
            $topCourses = Course::with(['reviews.user', 'reviews.reactions'])
                ->where('status', 'active')
                ->withAvg('reviews', 'rate')
                ->orderBy('reviews_avg_rate', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get top rated videos (by average rating)
            $topVideos = Video::with(['reviews.user', 'reviews.reactions'])
                ->where('status', 'active')
                ->withAvg('reviews', 'rate')
                ->orderBy('reviews_avg_rate', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get latest articles
            $latestArticles = Article::with(['reviews.user', 'reviews.reactions'])
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Create personalized suggestions (1 course + 1 remedy + 1 video)
            $personalizedSuggestions = $this->createPersonalizedSuggestions($topRemedies, $topCourses, $topVideos);

            return response()->json([
                'success' => true,
                'data' => [
                    'personalized_suggestions' => $personalizedSuggestions,
                    'top_remedies' => RemedyIndexResource::collection($topRemedies),
                    'top_courses' => CourseIndexResource::collection($topCourses),
                    'top_videos' => VideoIndexResource::collection($topVideos),
                    'latest_articles' => ArticleResource::collection($latestArticles),
                ],
                'message' => 'Learning content retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve learning content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create personalized suggestions with 1 course + 1 remedy + 1 video
     */
    private function createPersonalizedSuggestions($topRemedies, $topCourses, $topVideos): array
    {
        $suggestions = [];

        // Add 1 top course
        if ($topCourses->isNotEmpty()) {
            $suggestions[] = [
                'type' => 'course',
                'data' => new CourseIndexResource($topCourses->first())
            ];
        }

        // Add 1 top remedy
        if ($topRemedies->isNotEmpty()) {
            $suggestions[] = [
                'type' => 'remedy',
                'data' => new RemedyIndexResource($topRemedies->first())
            ];
        }

        // Add 1 top video
        if ($topVideos->isNotEmpty()) {
            $suggestions[] = [
                'type' => 'video',
                'data' => new VideoIndexResource($topVideos->first())
            ];
        }

        // Shuffle the suggestions to randomize the order
        shuffle($suggestions);

        return $suggestions;
    }
} 