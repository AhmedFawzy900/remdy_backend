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
                'type' => 'required|string|in:' . implode(',', LessonContentBlock::getAvailableTypes()),
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image_url' => 'nullable|url',
                'video_url' => 'nullable|url',
                'pdf_url' => 'nullable|url',
                'content' => 'nullable|array',
                'order' => 'required|integer|min:0',
                'is_active' => 'nullable|boolean',
                'remedy_id' => 'nullable|exists:remedies,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            
            // Validate content structure based on type
            $contentValidation = $this->validateContentByType($validatedData['type'], $validatedData['content'] ?? []);
            if (!$contentValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content validation failed',
                    'errors' => $contentValidation['errors']
                ], 422);
            }
            
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
                'type' => 'sometimes|required|string|in:' . implode(',', LessonContentBlock::getAvailableTypes()),
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'image_url' => 'nullable|url',
                'video_url' => 'nullable|url',
                'pdf_url' => 'nullable|url',
                'content' => 'nullable|array',
                'order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
                'remedy_id' => 'nullable|exists:remedies,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();
            
            // Validate content structure based on type
            if (isset($validatedData['content'])) {
                $contentValidation = $this->validateContentByType($validatedData['type'] ?? $block->type, $validatedData['content']);
                if (!$contentValidation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Content validation failed',
                        'errors' => $contentValidation['errors']
                    ], 422);
                }
            }
            
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
                'content' => [
                    'description' => 'List of items with image and title',
                    'structure' => [
                        'items' => 'array of objects with title, image_url',
                        'example' => [
                            'items' => [
                                ['title' => 'Item 1', 'image_url' => 'https://example.com/image1.jpg'],
                                ['title' => 'Item 2', 'image_url' => 'https://example.com/image2.jpg'],
                            ]
                        ]
                    ]
                ],
                'text' => [
                    'description' => 'Rich HTML text content (supports bold, font size, color)',
                    'structure' => [
                        'html_content' => 'string (HTML content)',
                        'example' => [
                            'html_content' => '<p><strong>Bold text</strong> with <span style="color: red;">colored text</span></p>'
                        ]
                    ]
                ],
                'video' => [
                    'description' => 'Video with title and link',
                    'structure' => [
                        'video_url' => 'string (URL)',
                        'title' => 'string',
                        'description' => 'string (optional)',
                        'example' => [
                            'video_url' => 'https://youtube.com/watch?v=example',
                            'title' => 'Video Title',
                            'description' => 'Video description'
                        ]
                    ]
                ],
                'remedy' => [
                    'description' => 'Remedy selection with details',
                    'structure' => [
                        'remedy_id' => 'integer (required)',
                        'example' => [
                            'remedy_id' => 1
                        ]
                    ]
                ],
                'tip' => [
                    'description' => 'Tip with image and rich text',
                    'structure' => [
                        'image_url' => 'string (URL)',
                        'html_content' => 'string (HTML content)',
                        'example' => [
                            'image_url' => 'https://example.com/tip-image.jpg',
                            'html_content' => '<p>This is a helpful tip with <strong>bold text</strong></p>'
                        ]
                    ]
                ],
                'image' => [
                    'description' => 'Image with optional URL link',
                    'structure' => [
                        'image_url' => 'string (URL, required)',
                        'link_url' => 'string (URL, optional)',
                        'alt_text' => 'string (optional)',
                        'example' => [
                            'image_url' => 'https://example.com/image.jpg',
                            'link_url' => 'https://example.com/link',
                            'alt_text' => 'Image description'
                        ]
                    ]
                ],
                'pdf' => [
                    'description' => 'PDF file with title and link',
                    'structure' => [
                        'pdf_url' => 'string (URL, required)',
                        'title' => 'string',
                        'description' => 'string (optional)',
                        'example' => [
                            'pdf_url' => 'https://example.com/document.pdf',
                            'title' => 'PDF Document Title',
                            'description' => 'PDF description'
                        ]
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

    /**
     * Validate content structure based on block type
     */
    private function validateContentByType(string $type, array $content): array
    {
        $errors = [];

        switch ($type) {
            case 'content':
                if (!isset($content['items']) || !is_array($content['items'])) {
                    $errors[] = 'Content type requires "items" array';
                } else {
                    foreach ($content['items'] as $index => $item) {
                        if (!isset($item['title']) || !isset($item['image_url'])) {
                            $errors[] = "Item at index {$index} must have 'title' and 'image_url'";
                        }
                    }
                }
                break;

            case 'text':
                if (!isset($content['html_content'])) {
                    $errors[] = 'Text type requires "html_content" field';
                }
                break;

            case 'video':
                if (!isset($content['video_url'])) {
                    $errors[] = 'Video type requires "video_url" field';
                }
                break;

            case 'remedy':
                if (!isset($content['remedy_id'])) {
                    $errors[] = 'Remedy type requires "remedy_id" field';
                }
                break;

            case 'tip':
                if (!isset($content['image_url']) || !isset($content['html_content'])) {
                    $errors[] = 'Tip type requires both "image_url" and "html_content" fields';
                }
                break;

            case 'image':
                if (!isset($content['image_url'])) {
                    $errors[] = 'Image type requires "image_url" field';
                }
                break;

            case 'pdf':
                if (!isset($content['pdf_url'])) {
                    $errors[] = 'PDF type requires "pdf_url" field';
                }
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
} 