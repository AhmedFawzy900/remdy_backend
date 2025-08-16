<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\RemedyType;
use App\Models\Remedy;
use App\Models\Course;
use App\Models\Video;
use App\Http\Resources\AdResource;
use App\Http\Resources\CourseIndexResource;
use App\Http\Resources\RemedyTypeResource;
use App\Http\Resources\RemedyResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\RemedyIndexResource;
use App\Http\Resources\VideoIndexResource;
use App\Http\Resources\VideoResource;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    /**
     * Get all home page data for mobile app.
     */
    public function index(): JsonResponse
    {
        try {
            // Get active ads for home placement
            $ads = Ad::active()
                ->where('type', Ad::TYPE_HOME)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get remedy types with remedy count
            $remedyTypes = RemedyType::withCount(['remedies' => function($query) {
                $query->where('status', 'active');
            }])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

            // Get latest remedies (eager-load plural relations for lists)
            $remedies = Remedy::with(['reviews.user','remedyType','bodySystem','diseaseRelation','remedyTypes','bodySystems','diseases'])->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get latest courses
            $courses = Course::with(['reviews.user'])->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get latest videos
            $videos = Video::with(['reviews.user'])->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'ads' => AdResource::collection($ads),
                    'remedy_types' => RemedyTypeResource::collection($remedyTypes),
                    'remedies' => RemedyIndexResource::collection($remedies),
                    'courses' => CourseIndexResource::collection($courses),
                    'videos' => VideoIndexResource::collection($videos),
                ],
                'message' => 'Home page data retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve home page data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
