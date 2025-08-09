<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonContentBlock;
use App\Http\Resources\LessonContentBlockResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LessonContentBlockController extends Controller
{
    /**
     * Display a listing of content blocks for a lesson.
     */
    public function index(Request $request, string $lessonId): JsonResponse
    {
        try {
            $lesson = Lesson::find($lessonId);

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            $query = $lesson->contentBlocks();

            // Filter by type
            if ($request->has('type') && $request->type) {
                $query->ofType($request->type);
            }

            // Filter by active status
            if ($request->has('active') && $request->active !== null) {
                $query->where('is_active', $request->boolean('active'));
            }

            // Sort by order
            $blocks = $query->ordered()->get();

            return response()->json([
                'success' => true,
                'data' => LessonContentBlockResource::collection($blocks),
                'message' => 'Content blocks retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve content blocks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created content block.
     */
    public function store(Request $request, string $lessonId): JsonResponse
    {
        try {
            $lesson = Lesson::find($lessonId);

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'required|string|max:100',
                'content' => 'required|array',
                'order' => 'required|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            
            // Check if order already exists and handle conflicts
            $existingBlock = $lesson->contentBlocks()->where('order', $validatedData['order'])->first();
            if ($existingBlock) {
                // Shift existing blocks to make room
                $lesson->contentBlocks()
                    ->where('order', '>=', $validatedData['order'])
                    ->increment('order');
            }

            $validatedData['lesson_id'] = $lessonId;
            
            $block = LessonContentBlock::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => new LessonContentBlockResource($block),
                'message' => 'Content block created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create content block',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified content block.
     */
    public function show(string $lessonId, string $blockId): JsonResponse
    {
        try {
            $block = LessonContentBlock::where('lesson_id', $lessonId)
                ->where('id', $blockId)
                ->first();

            if (!$block) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content block not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new LessonContentBlockResource($block),
                'message' => 'Content block retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve content block',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified content block.
     */
    public function update(Request $request, string $lessonId, string $blockId): JsonResponse
    {
        try {
            $block = LessonContentBlock::where('lesson_id', $lessonId)
                ->where('id', $blockId)
                ->first();

            if (!$block) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content block not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|required|string|max:100',
                'content' => 'sometimes|required|array',
                'order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            $block->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => new LessonContentBlockResource($block),
                'message' => 'Content block updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update content block',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified content block.
     */
    public function destroy(string $lessonId, string $blockId): JsonResponse
    {
        try {
            $block = LessonContentBlock::where('lesson_id', $lessonId)
                ->where('id', $blockId)
                ->first();

            if (!$block) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content block not found'
                ], 404);
            }

            $block->delete();

            return response()->json([
                'success' => true,
                'message' => 'Content block deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete content block',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder content blocks within a lesson.
     */
    public function reorder(Request $request, string $lessonId): JsonResponse
    {
        try {
            $lesson = Lesson::find($lessonId);

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'blocks' => 'required|array',
                'blocks.*.id' => 'required|exists:lesson_content_blocks,id',
                'blocks.*.order' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::transaction(function () use ($request, $lessonId) {
                foreach ($request->blocks as $blockData) {
                    LessonContentBlock::where('id', $blockData['id'])
                        ->where('lesson_id', $lessonId)
                        ->update(['order' => $blockData['order']]);
                }
            });

            // Return updated blocks
            $blocks = $lesson->contentBlocks()->ordered()->get();

            return response()->json([
                'success' => true,
                'data' => LessonContentBlockResource::collection($blocks),
                'message' => 'Content blocks reordered successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder content blocks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle content block active status.
     */
    public function toggleStatus(string $lessonId, string $blockId): JsonResponse
    {
        try {
            $block = LessonContentBlock::where('lesson_id', $lessonId)
                ->where('id', $blockId)
                ->first();

            if (!$block) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content block not found'
                ], 404);
            }

            $block->is_active = !$block->is_active;
            $block->save();

            return response()->json([
                'success' => true,
                'data' => new LessonContentBlockResource($block),
                'message' => 'Content block status updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update content block status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available content block types and their structure examples.
     */
    public function getTypes(): JsonResponse
    {
        try {
            $types = [
                'video' => [
                    'description' => 'Video content with title, description, and link',
                    'structure' => [
                        'title' => 'string',
                        'description' => 'string',
                        'link' => 'string (URL)',
                        'thumbnail' => 'string (URL, optional)',
                        'duration' => 'string (optional)',
                    ]
                ],
                'image' => [
                    'description' => 'Image with title and description',
                    'structure' => [
                        'title' => 'string',
                        'description' => 'string',
                        'image_url' => 'string (URL)',
                        'alt_text' => 'string (optional)',
                    ]
                ],
                'text' => [
                    'description' => 'Rich text content',
                    'structure' => [
                        'title' => 'string',
                        'content' => 'string (HTML supported)',
                        'style' => 'string (optional: paragraph, heading, etc.)',
                    ]
                ],
                'remedy' => [
                    'description' => 'Remedy selection with details',
                    'structure' => [
                        'title' => 'string',
                        'remedy_id' => 'integer (optional)',
                        'description' => 'string',
                        'image_url' => 'string (URL, optional)',
                        'product_link' => 'string (URL, optional)',
                    ]
                ],
                'ingredients' => [
                    'description' => 'List of ingredients',
                    'structure' => [
                        'title' => 'string',
                        'items' => 'array of objects with title, image_url, description',
                    ]
                ],
                'tips' => [
                    'description' => 'Tips and advice section',
                    'structure' => [
                        'title' => 'string',
                        'description' => 'string',
                        'items' => 'array of objects with title, content, image_url',
                    ]
                ],
                'activities' => [
                    'description' => 'Interactive activities section',
                    'structure' => [
                        'title' => 'string',
                        'description' => 'string',
                        'items' => 'array of objects with title, description, image_url',
                    ]
                ],
                'whats_included' => [
                    'description' => 'What is included in the lesson',
                    'structure' => [
                        'title' => 'string',
                        'items' => 'array of objects with title, image_url, description',
                    ]
                ],
                'instructions' => [
                    'description' => 'Step-by-step instructions',
                    'structure' => [
                        'title' => 'string',
                        'steps' => 'array of objects with step_number, title, description, image_url',
                    ]
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $types,
                'message' => 'Content block types retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve content block types',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 