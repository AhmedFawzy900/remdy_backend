<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\CoursePurchase;
use App\Models\LessonProgress;
use App\Http\Resources\Mobile\CourseResource;
use App\Http\Resources\Mobile\CourseDetailResource;
use App\Http\Resources\Mobile\MyCourseResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Get all available courses for purchase
     */
    public function availableCourses(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            $query = Course::with(['instructor', 'lessons' => function($query) {
                $query->active()->ordered();
            }]);

            // Filter by category/tags if needed
            if ($request->has('category') && $request->category) {
                $query->where('category', $request->category);
            }

            // Filter by price range
            if ($request->has('min_price') && $request->min_price) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price') && $request->max_price) {
                $query->where('price', '<=', $request->max_price);
            }

            // Filter by instructor
            if ($request->has('instructor_id') && $request->instructor_id) {
                $query->where('instructor_id', $request->instructor_id);
            }

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            if (in_array($sortBy, ['title', 'price', 'created_at', 'rating'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100);
            $courses = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => CourseResource::collection($courses),
                'pagination' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'per_page' => $courses->perPage(),
                    'total' => $courses->total(),
                    'from' => $courses->firstItem(),
                    'to' => $courses->lastItem(),
                    'has_more_pages' => $courses->hasMorePages(),
                ],
                'message' => 'Available courses retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available courses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase a course
     */
    public function purchaseCourse(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                // 'payment_method' => 'required|string|in:credit_card,paypal,apple_pay,google_pay',
                // 'payment_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $course = Course::find($request->course_id);
            
            // Check if user already purchased this course
            $existingPurchase = CoursePurchase::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existingPurchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already purchased this course'
                ], 400);
            }

            // Here you would integrate with your payment gateway
            // For now, we'll simulate a successful payment
            
            // Create course purchase record
            $purchase = CoursePurchase::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                // 'payment_method' => $request->payment_method,
                // 'payment_token' => $request->payment_token,
                'amount_paid' => $course->price,
                'status' => 'completed',
                'purchased_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'purchase_id' => $purchase->id,
                    'course_id' => $course->id,
                    'amount_paid' => $purchase->amount_paid,
                    'purchased_at' => $purchase->purchased_at,
                ],
                'message' => 'Course purchased successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to purchase course',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get course details with purchase status
     */
    public function showCourse(string $courseId): JsonResponse
    {
        try {
            $purchase = null;
            $started = false;
            $user = auth('sanctum')->user();

            if($user){
            $course = Course::with([
                'instructors', 
                'lessons' => function($query) {
                    $query->active()->ordered();
                },
            ])->find($courseId);

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Check if user purchased this course
            $purchase = CoursePurchase::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', 'completed')
                ->first();

            // Check if user started this course
            $started = false;
            if ($purchase) {
                $started = LessonProgress::where('user_id', $user->id)
                    ->whereIn('lesson_id', $course->lessons->pluck('id'))
                    ->exists();
            }
        }
        else{
            $course = Course::with([
                'instructors', 
                'lessons' => function($query) {
                    $query->active()->ordered();
                },
            ])->find($courseId);
        }
        if(!$course){
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }
        if($user){
            return response()->json([
                'success' => true,
                'data' => array_merge((new CourseDetailResource($course, $purchase, $started))->toArray(request()), [
                'ads' => \App\Http\Resources\AdResource::collection(\App\Models\Ad::active()->forPlacement(\App\Models\Ad::TYPE_COURSE, (int)$course->id)->orderBy('created_at', 'desc')->get()),
            ]),
                'message' => 'Course details retrieved successfully'
            ], 200);
        }
        else{
            return response()->json([
                'success' => true,
                'data' => array_merge((new CourseDetailResource($course))->toArray(request()), [
                'ads' => \App\Http\Resources\AdResource::collection(\App\Models\Ad::active()->forPlacement(\App\Models\Ad::TYPE_COURSE, (int)$course->id)->orderBy('created_at', 'desc')->get()),
            ]),
                'message' => 'Course details retrieved successfully'
            ], 200);
        }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a course (mark as started)
     */
    public function startCourse(string $courseId): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            // Check if user purchased this course
            $purchase = CoursePurchase::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', 'completed')
                ->first();

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must purchase this course before starting it'
                ], 400);
            }

            $course = Course::with('lessons')->find($courseId);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Mark course as started by creating progress records for all lessons
            foreach ($course->lessons as $lesson) {
                LessonProgress::firstOrCreate([
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                    'course_id' => $courseId,
                ], [
                    'status' => 'not_started',
                    'started_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Course started successfully',
                'data' => [
                    'course_id' => $courseId,
                    'started_at' => now(),
                    'total_lessons' => $course->lessons->count(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start course',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's purchased and started courses with progress
     */
    public function myCourses(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            $query = Course::with([
                'instructors',
                'lessons' => function($query) {
                    $query->active()->ordered();
                }
            ])->whereHas('purchases', function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('status', 'completed');
            });

            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'started') {
                    $query->whereHas('lessonProgress', function($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
                } elseif ($request->status === 'not_started') {
                    $query->whereDoesntHave('lessonProgress', function($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
                }
            }

            // Sort
            $sortBy = $request->get('sort_by', 'purchased_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if ($sortBy === 'purchased_at') {
                $query->join('course_purchases', 'courses.id', '=', 'course_purchases.course_id')
                      ->where('course_purchases.user_id', $user->id)
                      ->orderBy('course_purchases.purchased_at', $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100);
            $courses = $query->paginate($perPage);

            // Calculate progress for each course
           
            $coursesWithProgress = $courses->getCollection()->map(function($course) use ($user) {
                
                $totalLessons = $course->lessons->count();
                $completedLessons = LessonProgress::where('user_id', $user->id)
                    ->where('course_id', $course->course_id)
                    ->where('status', 'completed')
                    ->count();
                
                $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 1) : 0;
                
                return [
                    'course' => $course,
                    'progress' => $progress,
                    'total_lessons' => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'started' => $completedLessons > 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => MyCourseResource::collection($coursesWithProgress),
               
                'pagination' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'per_page' => $courses->perPage(),
                    'total' => $courses->total(),
                    'from' => $courses->firstItem(),
                    'to' => $courses->lastItem(),
                    'has_more_pages' => $courses->hasMorePages(),
                ],
                'message' => 'My courses retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve my courses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark lesson as completed
     */
    public function completeLesson(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'lesson_id' => 'required|exists:lessons,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
             
            // Check if user purchased this course
            $purchase = CoursePurchase::where('user_id', $user->id)
                ->where('course_id', $request->course_id)
                ->where('status', 'completed')
                ->first();

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must purchase this course to access lessons'
                ], 400);
            }

            // Check if lesson belongs to the course
            $lesson = Lesson::where('id', $request->lesson_id)
                ->where('course_id', $request->course_id)
                ->first();

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found in this course'
                ], 404);
            }

            // Update or create lesson progress
            $progress = LessonProgress::updateOrCreate([
                'user_id' => $user->id,
                'lesson_id' => $request->lesson_id,
                'course_id' => $request->course_id,
            ], [
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Calculate course progress
            $course = Course::find($request->course_id);
            $totalLessons = $course->lessons()->active()->count();
            $completedLessons = LessonProgress::where('user_id', $user->id)
                ->where('course_id', $request->course_id)
                ->where('status', 'completed')
                ->count();
            
            $progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 1) : 0;

            return response()->json([
                'success' => true,
                'message' => 'Lesson marked as completed',
                'data' => [
                    'lesson_id' => $request->lesson_id,
                    'course_id' => $request->course_id,
                    'completed_at' => $progress->completed_at,
                    'course_progress' => $progressPercentage,
                    'completed_lessons' => $completedLessons,
                    'total_lessons' => $totalLessons,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all lessons for a specific course (with access control)
     */
    public function getLessonsByCourse(string $courseId, Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            // Check if user purchased this course
            $purchase = CoursePurchase::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', 'completed')
                ->first();

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must purchase this course to access lessons'
                ], 400);
            }

            // Get lessons with content blocks
            $lessons = Lesson::with(['contentBlocks' => function($query) {
                $query->with('remedy')->active()->ordered();
            }])->where('course_id', $courseId)
                ->active()
                ->ordered()
                ->get();

            // Get progress for each lesson
            $lessonsWithProgress = $lessons->map(function($lesson) use ($user, $courseId) {
                $progress = LessonProgress::where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->where('course_id', $courseId)
                    ->first();

                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'description' => $lesson->description,
                    'image' => $lesson->image,
                    'status' => $lesson->status,
                    'content_blocks_count' => $lesson->contentBlocks->count(),
                    'progress' => [
                        'is_started' => $progress && $progress->status !== 'not_started',
                        'is_completed' => $progress && $progress->status === 'completed',
                        'started_at' => $progress ? $progress->started_at : null,
                        'completed_at' => $progress ? $progress->completed_at : null,
                    ],
                    'created_at' => $lesson->created_at,
                    'updated_at' => $lesson->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'course_id' => $courseId,
                    'lessons' => $lessonsWithProgress,
                    'total_lessons' => $lessons->count(),
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
     * Get lesson progress summary for a course
     */
    public function getLessonProgressSummary(string $courseId): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            // Check if user purchased this course
            $purchase = CoursePurchase::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', 'completed')
                ->first();

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must purchase this course to access progress'
                ], 400);
            }

            // Get course with lessons
            $course = Course::with(['lessons' => function($query) {
                $query->active()->ordered();
            }])->find($courseId);

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $totalLessons = $course->lessons->count();
            $completedLessons = LessonProgress::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', 'completed')
                ->count();
            
            $startedLessons = LessonProgress::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', '!=', 'not_started')
                ->count();

            $progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 1) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'course_id' => $courseId,
                    'course_title' => $course->title,
                    'total_lessons' => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'started_lessons' => $startedLessons,
                    'remaining_lessons' => $totalLessons - $completedLessons,
                    'progress_percentage' => $progressPercentage,
                    'is_started' => $startedLessons > 0,
                    'is_completed' => $completedLessons === $totalLessons && $totalLessons > 0,
                ],
                'message' => 'Lesson progress summary retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lesson progress summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a specific lesson (mark as started)
     */
    public function startLesson(string $courseId, string $lessonId): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            // Check if user purchased this course
            $purchase = CoursePurchase::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', 'completed')
                ->first();

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must purchase this course to access lessons'
                ], 400);
            }

            // Check if lesson exists and belongs to the course
            $lesson = Lesson::where('id', $lessonId)
                ->where('course_id', $courseId)
                ->active()
                ->first();

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found or not active'
                ], 404);
            }

            // Create or update lesson progress
            $progress = LessonProgress::firstOrCreate([
                'user_id' => $user->id,
                'lesson_id' => $lessonId,
                'course_id' => $courseId,
            ], [
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            // If progress already existed, update it
            if ($progress->wasRecentlyCreated === false) {
                $progress->update([
                    'status' => 'in_progress',
                    'started_at' => $progress->started_at ?? now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lesson started successfully',
                'data' => [
                    'lesson_id' => $lessonId,
                    'course_id' => $courseId,
                    'status' => $progress->status,
                    'started_at' => $progress->started_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get next lesson in course
     */
    public function getNextLesson(string $courseId, string $lessonId): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            // Check if user purchased this course
            $purchase = CoursePurchase::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', 'completed')
                ->first();

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must purchase this course to access lessons'
                ], 400);
            }

            // Get current lesson
            $currentLesson = Lesson::where('id', $lessonId)
                ->where('course_id', $courseId)
                ->first();

            if (!$currentLesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current lesson not found'
                ], 404);
            }

            // Get next lesson
            $nextLesson = Lesson::where('course_id', $courseId)
                ->where('created_at', '>', $currentLesson->created_at)
                ->active()
                ->ordered()
                ->first();

            if (!$nextLesson) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'has_next' => false,
                        'next_lesson' => null,
                        'message' => 'This is the last lesson in the course'
                    ],
                    'message' => 'No next lesson available'
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'has_next' => true,
                    'next_lesson' => [
                        'id' => $nextLesson->id,
                        'title' => $nextLesson->title,
                        'description' => $nextLesson->description,
                        'image' => $nextLesson->image,
                        'status' => $nextLesson->status,
                    ]
                ],
                'message' => 'Next lesson retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve next lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lesson details with completion status
     */
    public function showLesson(string $courseId, string $lessonId): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            
            // Check if user purchased this course
            $purchase = CoursePurchase::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', 'completed')
                ->first();

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must purchase this course to access lessons'
                ], 400);
            }

            $lesson = Lesson::with(['contentBlocks' => function($query) {
                $query->with('remedy')->ordered();
            }])->where('id', $lessonId)
                ->where('course_id', $courseId)
                ->first();

            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            // Get lesson progress
            $progress = LessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $lessonId)
                ->where('course_id', $courseId)
                ->first();

            $isCompleted = $progress && $progress->status === 'completed';
            $isStarted = $progress && $progress->status !== 'not_started';

            // Transform lesson data for mobile
            $lessonData = [
                'id' => $lesson->id,
                'course_id' => $lesson->course_id,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'image' => $lesson->image,
                'status' => $lesson->status,
                'content_blocks' => $lesson->contentBlocks->map(function($block) {
                    $blockData = [
                        'id' => $block->id,
                        'type' => $block->type,
                        'title' => $block->title,
                        'description' => $block->description,
                        'image_url' => $block->image_url,
                        'video_url' => $block->video_url,
                        'content' => $block->content,
                        'order' => $block->order,
                        'is_active' => $block->is_active,
                    ];

                    // Add remedy data if this is a remedy block
                    if ($block->type === 'remedy' && $block->remedy) {
                        $blockData['remedy'] = [
                            'id' => $block->remedy->id,
                            'title' => $block->remedy->title,
                            'description' => $block->remedy->description,
                            'main_image_url' => $block->remedy->main_image_url,
                            'ingredients' => $block->remedy->ingredients ?? [],
                            'instructions' => $block->remedy->instructions ?? [],
                            'benefits' => $block->remedy->benefits ?? [],
                            'precautions' => $block->remedy->precautions ?? [],
                            'product_link' => $block->remedy->product_link,
                            'status' => $block->remedy->status,
                        ];
                    }

                    return $blockData;
                }),
                'created_at' => $lesson->created_at,
                'updated_at' => $lesson->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'lesson' => $lessonData,
                    'progress' => [
                        'is_started' => $isStarted,
                        'is_completed' => $isCompleted,
                        'started_at' => $progress ? $progress->started_at : null,
                        'completed_at' => $progress ? $progress->completed_at : null,
                    ]
                ],
                'message' => 'Lesson details retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lesson details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 