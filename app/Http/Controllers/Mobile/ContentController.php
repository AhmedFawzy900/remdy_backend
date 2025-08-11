<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Remedy;
use App\Models\Article;
use App\Models\Course;
use App\Models\Video;
use App\Models\Review;
use App\Http\Resources\RemedyResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\VideoResource;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    /**
     * Get all content with reviews for mobile app.
     */
    public function getAllContent(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $limit = min($limit, 50); // Limit max to 50

            // Get remedies with reviews
            $remedies = Remedy::with(['remedyType', 'bodySystem', 'reviews.user'])
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Get articles with reviews
            $articles = Article::with(['reviews.user'])
                ->where('status', 'active')
                ->where(function($query) {
                    $query->whereNull('plans')
                          ->orWhere('plans', 'free');
                })
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Get courses with reviews
            $courses = Course::with(['reviews.user'])
                ->where('status', 'active')
                ->where('plan', 'free')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Get videos with reviews
            $videos = Video::with(['reviews.user'])
                ->where('status', 'active')
                ->where(function($query) {
                    $query->whereNull('visiblePlans')
                          ->orWhere('visiblePlans', 'free');
                })
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'remedies' => RemedyResource::collection($remedies),
                    'articles' => ArticleResource::collection($articles),
                    'courses' => CourseResource::collection($courses),
                    'videos' => VideoResource::collection($videos),
                ],
                'message' => 'All content retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content by type with reviews.
     */
    public function getContentByType(Request $request, string $type): JsonResponse
    {
        try {
            $limit = $request->get('limit', 20);
            $limit = min($limit, 50);

            switch ($type) {
                case 'remedies':
                    $content = Remedy::with(['remedyType', 'bodySystem', 'reviews.user'])
                        ->where('status', 'active')
                        ->orderBy('created_at', 'desc')
                        ->limit($limit)
                        ->get();
                    $resource = RemedyResource::class;
                    break;

                case 'articles':
                    $content = Article::with(['reviews.user'])
                        ->where('status', 'active')
                        ->where(function($query) {
                            $query->whereNull('plans')
                                  ->orWhere('plans', 'free');
                        })
                        ->orderBy('created_at', 'desc')
                        ->limit($limit)
                        ->get();
                    $resource = ArticleResource::class;
                    break;

                case 'courses':
                    $content = Course::with(['reviews.user'])
                        ->where('status', 'active')
                        ->where('plan', 'free')
                        ->orderBy('created_at', 'desc')
                        ->limit($limit)
                        ->get();
                    $resource = CourseResource::class;
                    break;

                case 'videos':
                    $content = Video::with(['reviews.user'])
                        ->where('status', 'active')
                        ->where(function($query) {
                            $query->whereNull('visiblePlans')
                                  ->orWhere('visiblePlans', 'free');
                        })
                        ->orderBy('created_at', 'desc')
                        ->limit($limit)
                        ->get();
                    $resource = VideoResource::class;
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid content type'
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $resource::collection($content),
                'message' => ucfirst($type) . ' retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ' . $type,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific content item with reviews.
     */
    public function getContentItem(Request $request, string $type, string $id): JsonResponse
    {
        try {
            switch ($type) {
                case 'remedies':
                    $item = Remedy::with(['remedyType', 'bodySystem', 'diseaseRelation', 'reviews.user', 'reviews.reactions'])
                        ->where('status', 'active')
                        ->find($id);
                    $resource = RemedyResource::class;
                    break;

                case 'articles':
                    $item = Article::with(['reviews.user'])
                        ->where('status', 'active')
                        ->where(function($query) {
                            $query->whereNull('plans')
                                  ->orWhere('plans', 'free');
                        })
                        ->find($id);
                    $resource = ArticleResource::class;
                    break;

                case 'courses':
                    $item = Course::with(['reviews.user'])
                        ->where('status', 'active')
                        ->where('plan', 'free')
                        ->find($id);
                    $resource = CourseResource::class;
                    break;

                case 'videos':
                    $item = Video::with(['reviews.user', 'reviews.reactions'])
                        ->where('status', 'active')
                        ->find($id);
                    $resource = VideoResource::class;
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid content type'
                    ], 400);
            }

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => ucfirst($type) . ' not found'
                ], 404);
            }

            // Add related content based on type
            $response = [
                'success' => true,
                'data' => new $resource($item),
                'message' => ucfirst($type) . ' retrieved successfully'
            ];

            if ($type === 'remedies') {
                $relatedRemedies = $this->getRelatedRemedies($item);
                $response['related_remedies'] = RemedyResource::collection($relatedRemedies);
            }

            if ($type === 'videos') {
                $relatedVideos = $this->getRelatedVideos($item);
                $response['related_videos'] = VideoResource::collection($relatedVideos);
            }

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ' . $type,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured content with reviews.
     */
    public function getFeaturedContent(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 5);
            $limit = min($limit, 20);

            // Get featured remedies
            $featuredRemedies = Remedy::with(['remedyType', 'bodySystem', 'reviews.user'])
                ->where('status', 'active')
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Get featured articles
            $featuredArticles = Article::with(['reviews.user'])
                ->where('status', 'active')
                ->where('is_featured', true)
                ->where(function($query) {
                    $query->whereNull('plans')
                          ->orWhere('plans', 'free');
                })
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Get featured courses
            $featuredCourses = Course::with(['reviews.user'])
                ->where('status', 'active')
                ->where('is_featured', true)
                ->where('plan', 'free')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Get featured videos
            $featuredVideos = Video::with(['reviews.user'])
                ->where('status', 'active')
                ->where('is_featured', true)
                ->where(function($query) {
                    $query->whereNull('visiblePlans')
                          ->orWhere('visiblePlans', 'free');
                })
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'remedies' => RemedyResource::collection($featuredRemedies),
                    'articles' => ArticleResource::collection($featuredArticles),
                    'courses' => CourseResource::collection($featuredCourses),
                    'videos' => VideoResource::collection($featuredVideos),
                ],
                'message' => 'Featured content retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search content across all types.
     */
    public function searchContent(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q');
            $type = $request->get('type', 'all');
            $limit = $request->get('limit', 20);
            $limit = min($limit, 50);

            if (!$query) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query is required'
                ], 400);
            }

            $results = [];

            if ($type === 'all' || $type === 'remedies') {
                $remedies = Remedy::with(['remedyType', 'bodySystem', 'reviews.user'])
                    ->where('status', 'active')
                    ->where(function($q) use ($query) {
                        $q->where('title', 'like', '%' . $query . '%')
                          ->orWhere('description', 'like', '%' . $query . '%')
                          ->orWhere('disease', 'like', '%' . $query . '%');
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
                $results['remedies'] = RemedyResource::collection($remedies);
            }

            if ($type === 'all' || $type === 'articles') {
                $articles = Article::with(['reviews.user'])
                    ->where('status', 'active')
                    ->where(function($q) use ($query) {
                        $q->where('title', 'like', '%' . $query . '%')
                          ->orWhere('description', 'like', '%' . $query . '%');
                    })
                    ->where(function($query) {
                        $query->whereNull('plans')
                              ->orWhere('plans', 'free');
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
                $results['articles'] = ArticleResource::collection($articles);
            }

            if ($type === 'all' || $type === 'courses') {
                $courses = Course::with(['reviews.user'])
                    ->where('status', 'active')
                    ->where(function($q) use ($query) {
                        $q->where('title', 'like', '%' . $query . '%')
                          ->orWhere('description', 'like', '%' . $query . '%');
                    })
                    ->where('plan', 'free')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
                $results['courses'] = CourseResource::collection($courses);
            }

            if ($type === 'all' || $type === 'videos') {
                $videos = Video::with(['reviews.user'])
                    ->where('status', 'active')
                    ->where(function($q) use ($query) {
                        $q->where('title', 'like', '%' . $query . '%')
                          ->orWhere('description', 'like', '%' . $query . '%');
                    })
                    ->where(function($query) {
                        $query->whereNull('visiblePlans')
                              ->orWhere('visiblePlans', 'free');
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
                $results['videos'] = VideoResource::collection($videos);
            }

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Search results retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get related remedies from the same category with similar names
     */
    private function getRelatedRemedies(Remedy $remedy): \Illuminate\Database\Eloquent\Collection
    {
        // Get remedies from the same category (remedy_type_id)
        $relatedRemedies = Remedy::where('id', '!=', $remedy->id)
            ->where('remedy_type_id', $remedy->remedy_type_id)
            ->where('status', 'active')
            ->with(['remedyType', 'bodySystem', 'diseaseRelation', 'reviews.user', 'reviews.reactions'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // If we don't have enough remedies from the same category, add remedies with similar names
        if ($relatedRemedies->count() < 6) {
            $remainingCount = 6 - $relatedRemedies->count();
            
            // Get remedies with similar names (using LIKE for partial matching)
            $similarNameRemedies = Remedy::where('id', '!=', $remedy->id)
                ->where('id', 'not in', $relatedRemedies->pluck('id'))
                ->where('status', 'active')
                ->where(function($query) use ($remedy) {
                    $query->where('title', 'LIKE', '%' . $remedy->title . '%')
                          ->orWhere('disease', 'LIKE', '%' . $remedy->disease . '%')
                          ->orWhere('title', 'LIKE', '%' . $remedy->disease . '%')
                          ->orWhere('disease', 'LIKE', '%' . $remedy->title . '%');
                })
                ->with(['remedyType', 'bodySystem', 'diseaseRelation', 'reviews.user', 'reviews.reactions'])
                ->orderBy('created_at', 'desc')
                ->limit($remainingCount)
                ->get();

            $relatedRemedies = $relatedRemedies->merge($similarNameRemedies);
        }

        return $relatedRemedies;
    }

    /**
     * Get related videos with similar content
     */
    private function getRelatedVideos(Video $video): \Illuminate\Database\Eloquent\Collection
    {
        // Get videos with similar titles or descriptions
        $relatedVideos = Video::where('id', '!=', $video->id)
            ->where('status', 'active')
            ->where(function($query) use ($video) {
                $query->where('title', 'LIKE', '%' . $video->title . '%')
                      ->orWhere('description', 'LIKE', '%' . $video->title . '%')
                      ->orWhere('title', 'LIKE', '%' . $video->description . '%')
                      ->orWhere('description', 'LIKE', '%' . $video->description . '%');
            })
            ->with(['reviews.user', 'reviews.reactions'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // If we don't have enough related videos, add latest videos
        if ($relatedVideos->count() < 6) {
            $remainingCount = 6 - $relatedVideos->count();
            
            $latestVideos = Video::where('id', '!=', $video->id)
                ->where('id', 'not in', $relatedVideos->pluck('id'))
                ->where('status', 'active')
                ->with(['reviews.user', 'reviews.reactions'])
                ->orderBy('created_at', 'desc')
                ->limit($remainingCount)
                ->get();

            $relatedVideos = $relatedVideos->merge($latestVideos);
        }

        return $relatedVideos;
    }
} 