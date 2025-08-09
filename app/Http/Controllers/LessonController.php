<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Course;
use App\Models\LessonContentBlock;
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
            $query = Lesson::with(['course', 'contentBlocks' => function($query) {
                $query->with('remedy')->ordered();
            }]);

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
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            if (in_array($sortBy, ['title', 'status', 'created_at', 'updated_at'])) {
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
                'status' => 'nullable|in:active,inactive',
                'content_blocks' => 'nullable|array',
                'content_blocks.*.type' => 'required_with:content_blocks|string|max:100',
                'content_blocks.*.title' => 'required_with:content_blocks|string|max:255',
                'content_blocks.*.description' => 'required_with:content_blocks|string',
                'content_blocks.*.image_url' => 'nullable|url',
                'content_blocks.*.video_url' => 'nullable|url',
                'content_blocks.*.remedy_id' => 'nullable|exists:remedies,id',
                'content_blocks.*.content' => 'nullable|array',
                'content_blocks.*.order' => 'required_with:content_blocks|integer|min:0',
                'content_blocks.*.is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            $contentBlocks = $validatedData['content_blocks'] ?? [];
            unset($validatedData['content_blocks']);

            // Create lesson
            $lesson = Lesson::create($validatedData);

            // Create content blocks if provided
            if (!empty($contentBlocks)) {
                foreach ($contentBlocks as $blockData) {
                    $blockData['lesson_id'] = $lesson->id;
                    
                    // Handle ingredients and instructions arrays
                    if (in_array($blockData['type'], ['ingredients', 'instructions'])) {
                        $content = [];
                        
                        if ($blockData['type'] === 'ingredients' && isset($blockData['content']['items'])) {
                            $content['items'] = array_map(function($item) {
                                return array_intersect_key($item, array_flip(['title', 'image_url']));
                            }, $blockData['content']['items']);
                        }
                        
                        if ($blockData['type'] === 'instructions' && isset($blockData['content']['steps'])) {
                            $content['steps'] = array_map(function($step) {
                                return array_intersect_key($step, array_flip(['title', 'image_url']));
                            }, $blockData['content']['steps']);
                        }
                        
                        // Only set content if we have data
                        if (!empty($content)) {
                            $blockData['content'] = $content;
                        }
                    }

                    // Handle remedy type - store remedy_id and basic info
                    if ($blockData['type'] === 'remedy' && isset($blockData['remedy_id'])) {
                        // Keep remedy_id for relationship
                        // Content will be populated from the remedy relationship
                    }
                    
                    LessonContentBlock::create($blockData);
                }
            }

            // Load the lesson with content blocks and remedy relationships
            $lesson->load(['contentBlocks' => function($query) {
                $query->with('remedy')->ordered();
            }]);

            return response()->json([
                'success' => true,
                'data' => new LessonResource($lesson),
                'message' => 'Lesson created successfully with content blocks'
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
            $lesson = Lesson::with(['course', 'contentBlocks' => function($query) {
                $query->with('remedy')->ordered();
            }])->find($id);

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
                'status' => 'nullable|in:active,inactive',
                'content_blocks' => 'nullable|array',
                'content_blocks.*.id' => 'nullable|exists:lesson_content_blocks,id',
                'content_blocks.*.type' => 'required_with:content_blocks|string|max:100',
                'content_blocks.*.title' => 'required_with:content_blocks|string|max:255',
                'content_blocks.*.description' => 'required_with:content_blocks|string',
                'content_blocks.*.image_url' => 'nullable|url',
                'content_blocks.*.video_url' => 'nullable|url',
                'content_blocks.*.remedy_id' => 'nullable|exists:remedies,id',
                'content_blocks.*.content' => 'nullable|array',
                'content_blocks.*.order' => 'required_with:content_blocks|integer|min:0',
                'content_blocks.*.is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            $contentBlocks = $validatedData['content_blocks'] ?? [];
            unset($validatedData['content_blocks']);

            // Update lesson basic info
            $lesson->update($validatedData);

            // Handle content blocks update
            if (!empty($contentBlocks)) {
                foreach ($contentBlocks as $blockData) {
                    if (isset($blockData['id'])) {
                        // Update existing block
                        $existingBlock = $lesson->contentBlocks()->find($blockData['id']);
                        if ($existingBlock) {
                            // Handle ingredients and instructions arrays
                            if (in_array($blockData['type'], ['ingredients', 'instructions'])) {
                                $content = [];
                                
                                if ($blockData['type'] === 'ingredients' && isset($blockData['content']['items'])) {
                                    $content['items'] = array_map(function($item) {
                                        return array_intersect_key($item, array_flip(['title', 'image_url']));
                                    }, $blockData['content']['items']);
                                }
                                
                                if ($blockData['type'] === 'instructions' && isset($blockData['content']['steps'])) {
                                    $content['steps'] = array_map(function($step) {
                                        return array_intersect_key($step, array_flip(['title', 'image_url']));
                                    }, $blockData['content']['steps']);
                                }
                                
                                if (!empty($content)) {
                                    $blockData['content'] = $content;
                                }
                            }
                            
                            $existingBlock->update($blockData);
                        }
                    } else {
                        // Create new block
                        $blockData['lesson_id'] = $lesson->id;
                        
                        // Handle ingredients and instructions arrays
                        if (in_array($blockData['type'], ['ingredients', 'instructions'])) {
                            $content = [];
                            
                            if ($blockData['type'] === 'ingredients' && isset($blockData['content']['items'])) {
                                $content['items'] = array_map(function($item) {
                                    return array_intersect_key($item, array_flip(['title', 'image_url']));
                                }, $blockData['content']['items']);
                            }
                            
                            if ($blockData['type'] === 'instructions' && isset($blockData['content']['steps'])) {
                                $content['steps'] = array_map(function($step) {
                                    return array_intersect_key($step, array_flip(['title', 'image_url']));
                                }, $blockData['content']['steps']);
                            }
                            
                            if (!empty($content)) {
                                $blockData['content'] = $content;
                            }
                        }
                        
                        LessonContentBlock::create($blockData);
                    }
                }
            }

            // Load the lesson with updated content blocks and remedy relationships
            $lesson->load(['contentBlocks' => function($query) {
                $query->with('remedy');
            }]);

            return response()->json([
                'success' => true,
                'data' => new LessonResource($lesson),
                'message' => 'Lesson updated successfully with content blocks'
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

            $lessons = $course->lessons()->active()->with(['contentBlocks' => function($query) {
                $query->with('remedy')->ordered();
            }])->get();

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
