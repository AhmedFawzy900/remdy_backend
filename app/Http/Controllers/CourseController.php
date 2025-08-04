<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseIndexResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Course::with(['reviews.user']);

            // Filter by title
            if ($request->has('title') && $request->title) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            // Filter by plan
            if ($request->has('plan') && $request->plan) {
                $query->where('plan', $request->plan);
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // General search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%')
                      ->orWhere('overview', 'like', '%' . $search . '%');
                });
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            if (in_array($sortBy, ['title', 'plan', 'status', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Limit max per page to 100
            $courses = $query->paginate($perPage);
            
            // Manually load remedies for each course
            $courses->getCollection()->transform(function ($course) {
                $remedyIds = $course->selectedRemedies ?? [];
                $course->remedies = \App\Models\Remedy::with(['reviews.user'])->whereIn('id', $remedyIds)->get();
                return $course;
            });

            return response()->json([
                'success' => true,
                'data' => CourseIndexResource::collection($courses),
                'pagination' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'per_page' => $courses->perPage(),
                    'total' => $courses->total(),
                    'from' => $courses->firstItem(),
                    'to' => $courses->lastItem(),
                    'has_more_pages' => $courses->hasMorePages(),
                ],
                'message' => 'Courses retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses',
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
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'duration' => 'nullable|string|max:50',
                'sessionsNumber' => 'nullable|integer',
                'price' => 'nullable|numeric',
                'plan' => 'nullable|string|max:50',
                'overview' => 'nullable|string',
                'courseContent' => 'nullable|array',
                'courseContent.*.title' => 'required|string',
                'courseContent.*.image' => 'nullable|url',
                'instructors' => 'nullable|array',
                'instructors.*.name' => 'required|string',
                'instructors.*.description' => 'nullable|string',
                'instructors.*.image' => 'nullable|url',
                'selectedRemedies' => 'nullable|array',
                'selectedRemedies.*' => 'string',
                'relatedCourses' => 'nullable|array',
                'relatedCourses.*' => 'string',
                'status' => 'sometimes|in:active,inactive',
                'sessions' => 'nullable|array',
                'sessions.*.day' => 'nullable|integer',
                'sessions.*.title' => 'required|string',
                'sessions.*.description' => 'nullable|string',
                'sessions.*.videoUrl' => 'nullable|url',
                'sessions.*.videoDescription' => 'nullable|string',
                'sessions.*.lessonContent' => 'nullable|array',
                'sessions.*.lessonContent.*.title' => 'required|string',
                'sessions.*.lessonContent.*.image' => 'nullable|url',
                'sessions.*.remedies' => 'nullable|array',
                'sessions.*.remedies.*' => 'string',
                'sessions.*.tip' => 'nullable|string',
                'sessions.*.isCompleted' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $course = Course::create($request->all());
            
            // Manually load remedies
            $remedyIds = $course->selectedRemedies ?? [];
            $course->remedies = \App\Models\Remedy::with(['reviews.user'])->whereIn('id', $remedyIds)->get();

            return response()->json([
                'success' => true,
                'data' => new CourseResource($course),
                'message' => 'Course created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create course',
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
            $course = Course::with(['reviews.user', 'reviews.reactions'])->find($id);
            
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }
            
            // Manually load remedies
            $remedyIds = $course->selectedRemedies ?? [];
            $course->remedies = \App\Models\Remedy::with(['reviews.user'])->whereIn('id', $remedyIds)->get();

            return response()->json([
                'success' => true,
                'data' => new CourseResource($course),
                'message' => 'Course retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course',
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
            $course = Course::find($id);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }
            $validator = Validator::make($request->all(), [
                'image' => 'nullable|url',
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'duration' => 'nullable|string|max:50',
                'sessionsNumber' => 'nullable|integer',
                'price' => 'nullable|numeric',
                'plan' => 'nullable|string|max:50',
                'overview' => 'nullable|string',
                'courseContent' => 'nullable|array',
                'courseContent.*.title' => 'required|string',
                'courseContent.*.image' => 'nullable|url',
                'instructors' => 'nullable|array',
                'instructors.*.name' => 'required|string',
                'instructors.*.description' => 'nullable|string',
                'instructors.*.image' => 'nullable|url',
                'selectedRemedies' => 'nullable|array',
                'selectedRemedies.*' => 'string',
                'relatedCourses' => 'nullable|array',
                'relatedCourses.*' => 'string',
                'status' => 'sometimes|in:active,inactive',
                'sessions' => 'nullable|array',
                'sessions.*.day' => 'nullable|integer',
                'sessions.*.title' => 'required|string',
                'sessions.*.description' => 'nullable|string',
                'sessions.*.videoUrl' => 'nullable|url',
                'sessions.*.videoDescription' => 'nullable|string',
                'sessions.*.lessonContent' => 'nullable|array',
                'sessions.*.lessonContent.*.title' => 'required|string',
                'sessions.*.lessonContent.*.image' => 'nullable|url',
                'sessions.*.remedies' => 'nullable|array',
                'sessions.*.remedies.*' => 'string',
                'sessions.*.tip' => 'nullable|string',
                'sessions.*.isCompleted' => 'nullable|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $course->update($request->all());
            
            // Manually load remedies
            $remedyIds = $course->selectedRemedies ?? [];
            $course->remedies = \App\Models\Remedy::with(['reviews.user'])->whereIn('id', $remedyIds)->get();

            return response()->json([
                'success' => true,
                'data' => new CourseResource($course),
                'message' => 'Course updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update course',
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
            $course = Course::find($id);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }
            $course->delete();
            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course',
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
            $course = Course::find($id);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }
            $newStatus = $course->status === Course::STATUS_ACTIVE 
                ? Course::STATUS_INACTIVE 
                : Course::STATUS_ACTIVE;
            $course->update(['status' => $newStatus]);
            return response()->json([
                'success' => true,
                'data' => new CourseResource($course),
                'message' => 'Course status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle course status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured courses for mobile app.
     */
    public function featured(): JsonResponse
    {
        try {
            $courses = Course::where('status', 'active')
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Manually load remedies for each course
            $courses->transform(function ($course) {
                $remedyIds = $course->selectedRemedies ?? [];
                $course->remedies = \App\Models\Remedy::with(['reviews.user'])->whereIn('id', $remedyIds)->get();
                return $course;
            });

            return response()->json([
                'success' => true,
                'data' => CourseResource::collection($courses),
                'message' => 'Featured courses retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured courses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get latest courses for mobile app.
     */
    public function latest(): JsonResponse
    {
        try {
            $courses = Course::where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Manually load remedies for each course
            $courses->transform(function ($course) {
                $remedyIds = $course->selectedRemedies ?? [];
                $course->remedies = \App\Models\Remedy::with(['reviews.user'])->whereIn('id', $remedyIds)->get();
                return $course;
            });

            return response()->json([
                'success' => true,
                'data' => CourseResource::collection($courses),
                'message' => 'Latest courses retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve latest courses',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
