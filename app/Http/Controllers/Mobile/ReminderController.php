<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Models\Remedy;
use App\Models\Article;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReminderController extends Controller
{
    /**
     * Get element data based on reminder type.
     */
    private function getElementData($reminder)
    {
        $elementData = null;
        
        switch ($reminder->element_type) {
            case 'remedy':
                // Load remedy relationship specifically
                $remedy = Remedy::find($reminder->element_id);
                if ($remedy) {
                    $elementData = [
                        'id' => $remedy->id,
                        'name' => $remedy->title,
                        'description' => $remedy->description,
                        'image' => $remedy->main_image_url,
                        'status' => $remedy->status,
                    ];
                }
                break;
            case 'article':
                // Load article relationship specifically
                $article = Article::find($reminder->element_id);
                if ($article) {
                    $elementData = [
                        'id' => $article->id,
                        'title' => $article->title,
                        'content' => $article->content,
                        'image' => $article->image,
                        'status' => $article->status,
                    ];
                }
                break;
            case 'course':
                // Load course relationship specifically
                $course = Course::find($reminder->element_id);
                if ($course) {
                    $elementData = [
                        'id' => $course->id,
                        'title' => $course->title,
                        'description' => $course->description,
                        'image' => $course->image,
                        'status' => $course->status,
                    ];
                }
                break;
            case 'video':
                // Load video relationship specifically
                $video = Video::find($reminder->element_id);
                if ($video) {
                    $elementData = [
                        'id' => $video->id,
                        'title' => $video->title,
                        'description' => $video->description,
                        'video_url' => $video->video_url,
                        'thumbnail' => $video->thumbnail,
                        'status' => $video->status,
                    ];
                }
                break;
        }
        
        return $elementData;
    }

    /**
     * Create a new reminder.
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'element_type' => 'required|string|in:remedy,article,course,video',
                'element_id' => 'required|integer|min:1',
                'days' => 'nullable|array',
                'days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'time' => 'required|string|date_format:H:i',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();

            // Check if reminder already exists for the same element and time
            $existingReminder = Reminder::where('user_id', $user->id)
                ->where('element_type', $request->element_type)
                ->where('element_id', $request->element_id)
                ->where('time', $request->time)
                ->first();

            if ($existingReminder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reminder already exists for this element at this time'
                ], 409);
            }

            // Create the reminder
            $reminder = Reminder::create([
                'user_id' => $user->id,
                'element_type' => $request->element_type,
                'element_id' => $request->element_id,
                'days' => $request->days, // null for all days, array for specific days
                'time' => $request->time,
                'is_active' => true,
            ]);

            // Get the element data
            $elementData = $this->getElementData($reminder);

            return response()->json([
                'success' => true,
                'message' => 'Reminder created successfully',
                'data' => [
                    'id' => $reminder->id,
                    'element_type' => $reminder->element_type,
                    'element' => $elementData,
                    'days' => $reminder->days,
                    'day_names' => $reminder->day_names,
                    'time' => $reminder->time->format('H:i'),
                    'formatted_time' => $reminder->formatted_time,
                    'is_active' => $reminder->is_active,
                    'created_at' => $reminder->created_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reminder',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all reminders for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
           
            $perPage = $request->get('per_page', 15); // Default 15 items per page

            $reminders = Reminder::where('user_id', $user->id)
                ->orderBy('time')
                ->paginate($perPage);

            $remindersData = $reminders->getCollection()->map(function ($reminder) {
                $elementData = $this->getElementData($reminder);

                return [
                    'id' => $reminder->id,
                    'element_type' => $reminder->element_type,
                    'element' => $elementData,
                    'days' => $reminder->days,
                    'day_names' => $reminder->day_names,
                    'time' => $reminder->time->format('H:i'),
                    'formatted_time' => $reminder->formatted_time,
                    'is_active' => $reminder->is_active,
                    'created_at' => $reminder->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Reminders retrieved successfully',
                'data' => $remindersData,
                'pagination' => [
                    'current_page' => $reminders->currentPage(),
                    'last_page' => $reminders->lastPage(),
                    'per_page' => $reminders->perPage(),
                    'total' => $reminders->total(),
                    'from' => $reminders->firstItem(),
                    'to' => $reminders->lastItem(),
                    'has_more_pages' => $reminders->hasMorePages(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reminders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a reminder.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'days' => 'nullable|array',
                'days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'time' => 'required|string|date_format:H:i',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $reminder = Reminder::where('user_id', $user->id)->find($id);

            if (!$reminder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reminder not found'
                ], 404);
            }

            // Check if updated reminder would conflict with existing one
            $existingReminder = Reminder::where('user_id', $user->id)
                ->where('element_type', $reminder->element_type)
                ->where('element_id', $reminder->element_id)
                ->where('time', $request->time)
                ->where('id', '!=', $id)
                ->first();

            if ($existingReminder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reminder already exists for this element at this time'
                ], 409);
            }

            $reminder->update([
                'days' => $request->days,
                'time' => $request->time,
                'is_active' => $request->has('is_active') ? $request->is_active : $reminder->is_active,
            ]);

            // Get the element data
            $elementData = $this->getElementData($reminder);

            return response()->json([
                'success' => true,
                'message' => 'Reminder updated successfully',
                'data' => [
                    'id' => $reminder->id,
                    'element_type' => $reminder->element_type,
                    'element' => $elementData,
                    'days' => $reminder->days,
                    'day_names' => $reminder->day_names,
                    'time' => $reminder->time->format('H:i'),
                    'formatted_time' => $reminder->formatted_time,
                    'is_active' => $reminder->is_active,
                    'updated_at' => $reminder->updated_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reminder',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific reminder.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $reminder = Reminder::where('user_id', $user->id)->find($id);

            if (!$reminder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reminder not found'
                ], 404);
            }

            $reminder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reminder deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reminder',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete all reminders for the authenticated user.
     */
    public function deleteAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $deletedCount = Reminder::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All reminders deleted successfully',
                'data' => [
                    'deleted_count' => $deletedCount
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reminders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle reminder active status.
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $reminder = Reminder::where('user_id', $user->id)->find($id);

            if (!$reminder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reminder not found'
                ], 404);
            }

            $reminder->update([
                'is_active' => !$reminder->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reminder status updated successfully',
                'data' => [
                    'id' => $reminder->id,
                    'is_active' => $reminder->is_active
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reminder status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 