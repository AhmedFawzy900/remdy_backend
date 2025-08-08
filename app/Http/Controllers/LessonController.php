<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Course;
use App\Http\Resources\LessonResource;
use App\Http\Resources\LessonIndexResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Lesson::with(['course']);

            // Filter by course_id
            if ($request->has('course_id') && $request->course_id) {
                $query->where('course_id', $request->course_id);
            }

            // Filter by title
            if ($request->has('title') && $request->title) {
                $query->where('title', 'like', '%' . $request->title . '%');
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
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'order');
            $sortOrder = $request->get('sort_order', 'asc');
            if (in_array($sortBy, ['title', 'status', 'order', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Limit max per page to 100
            $lessons = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => LessonIndexResource::collection($lessons),
                'pagination' => [
                    'current_page' => $lessons->currentPage(),
                    'last_page' => $lessons->lastPage(),
                    'per_page' => $lessons->perPage(),
                    'total' => $lessons->total(),
                    'from' => $lessons->firstItem(),
                    'to' => $lessons->lastItem(),
                    'has_more_pages' => $lessons->hasMorePages(),
                ],
                'message' => 'Lessons retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lessons',
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
                'course_id' => 'required|exists:courses,id',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'image' => 'nullable|url',
                'whats_included' => 'nullable|array',
                'whats_included.*.image' => 'nullable|url',
                'whats_included.*.title' => 'nullable|string',
                'activities' => 'nullable|array',
                'activities.title' => 'nullable|string',
                'activities.items' => 'nullable|array',
                'activities.items.*.title' => 'nullable|string',
                'video' => 'nullable|array',
                'video.title' => 'nullable|string',
                'video.description' => 'nullable|string',
                'video.link' => 'nullable|url',
                'instructions' => 'nullable|array',
                'instructions.*.title' => 'nullable|string',
                'instructions.*.image' => 'nullable|url',
                'ingredients' => 'nullable|array',
                'ingredients.*.title' => 'nullable|string',
                'ingredients.*.image' => 'nullable|url',
                'tips' => 'nullable|array',
                'tips.title' => 'nullable|string',
                'tips.description' => 'nullable|string',
                'tips.content' => 'nullable|array',
                'tips.content.*.title' => 'nullable|string',
                'tips.content.*.image' => 'nullable|url',
                'status' => 'nullable|in:active,inactive',
                'order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            
            // Ensure JSON fields are properly initialized
            $validatedData['whats_included'] = $validatedData['whats_included'] ?? [];
            $validatedData['activities'] = $validatedData['activities'] ?? [];
            $validatedData['video'] = $validatedData['video'] ?? [];
            $validatedData['instructions'] = $validatedData['instructions'] ?? [];
            $validatedData['ingredients'] = $validatedData['ingredients'] ?? [];
            $validatedData['tips'] = $validatedData['tips'] ?? [];
            
            $lesson = Lesson::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => new LessonResource($lesson),
                'message' => 'Lesson created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lesson',
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
            $lesson = Lesson::with(['course'])->find($id);

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new LessonResource($lesson),
                'message' => 'Lesson retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lesson',
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
            $lesson = Lesson::find($id);

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'course_id' => 'sometimes|required|exists:courses,id',
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'image' => 'nullable|url',
                'whats_included' => 'nullable|array',
                'whats_included.*.image' => 'nullable|url',
                'whats_included.*.title' => 'nullable|string',
                'activities' => 'nullable|array',
                'activities.title' => 'nullable|string',
                'activities.items' => 'nullable|array',
                'activities.items.*.title' => 'nullable|string',
                'video' => 'nullable|array',
                'video.title' => 'nullable|string',
                'video.description' => 'nullable|string',
                'video.link' => 'nullable|url',
                'instructions' => 'nullable|array',
                'instructions.*.title' => 'nullable|string',
                'instructions.*.image' => 'nullable|url',
                'ingredients' => 'nullable|array',
                'ingredients.*.title' => 'nullable|string',
                'ingredients.*.image' => 'nullable|url',
                'tips' => 'nullable|array',
                'tips.title' => 'nullable|string',
                'tips.description' => 'nullable|string',
                'tips.content' => 'nullable|array',
                'tips.content.*.title' => 'nullable|string',
                'tips.content.*.image' => 'nullable|url',
                'status' => 'nullable|in:active,inactive',
                'order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            
            // Ensure JSON fields are properly initialized for updates
            if (isset($validatedData['whats_included'])) {
                $validatedData['whats_included'] = $validatedData['whats_included'] ?? [];
            }
            if (isset($validatedData['activities'])) {
                $validatedData['activities'] = $validatedData['activities'] ?? [];
            }
            if (isset($validatedData['video'])) {
                $validatedData['video'] = $validatedData['video'] ?? [];
            }
            if (isset($validatedData['instructions'])) {
                $validatedData['instructions'] = $validatedData['instructions'] ?? [];
            }
            if (isset($validatedData['ingredients'])) {
                $validatedData['ingredients'] = $validatedData['ingredients'] ?? [];
            }
            if (isset($validatedData['tips'])) {
                $validatedData['tips'] = $validatedData['tips'] ?? [];
            }
            
            $lesson->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => new LessonResource($lesson),
                'message' => 'Lesson updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson',
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
            $lesson = Lesson::find($id);

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            $lesson->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lesson deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle lesson status
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $lesson = Lesson::find($id);

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            $lesson->status = $lesson->status === Lesson::STATUS_ACTIVE 
                ? Lesson::STATUS_INACTIVE 
                : Lesson::STATUS_ACTIVE;
            $lesson->save();

            return response()->json([
                'success' => true,
                'data' => new LessonResource($lesson),
                'message' => 'Lesson status updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lessons by course
     */
    public function byCourse(string $courseId): JsonResponse
    {
        try {
            $course = Course::find($courseId);

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $lessons = $course->lessons()->active()->get();

            return response()->json([
                'success' => true,
                'data' => LessonResource::collection($lessons),
                'message' => 'Course lessons retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course lessons',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
