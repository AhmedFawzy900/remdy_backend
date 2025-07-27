<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Notification::with('user');
            // Filter by user_id
            if ($request->has('user_id') && $request->user_id) {
                $query->where('user_id', $request->user_id);
            }
            // Filter by admin_id
            if ($request->has('admin_id') && $request->admin_id) {
                $query->where('admin_id', $request->admin_id);
            }
            // Filter by type
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
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
                      ->orWhere('body', 'like', '%' . $search . '%');
                });
            }
            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            if (in_array($sortBy, ['title', 'type', 'status', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }
            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100);
            $notifications = $query->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => NotificationResource::collection($notifications),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                    'has_more_pages' => $notifications->hasMorePages(),
                ],
                'message' => 'Notifications retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
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
                'user_id' => 'nullable|exists:users,id',
                'admin_id' => 'nullable|exists:admins,id',
                'title' => 'required|string|max:255',
                'body' => 'required|string',
                'type' => 'sometimes|in:info,warning,success,error,custom',
                'status' => 'sometimes|in:unread,read,archived',
                'data' => 'nullable|array',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $notification = Notification::create([
                'user_id' => $request->user_id,
                'admin_id' => $request->admin_id,
                'title' => $request->title,
                'body' => $request->body,
                'type' => $request->type ?? Notification::TYPE_INFO,
                'status' => $request->status ?? Notification::STATUS_UNREAD,
                'data' => $request->data,
            ]);
            $notification->load('user');
            return response()->json([
                'success' => true,
                'data' => new NotificationResource($notification),
                'message' => 'Notification created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create notification',
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
            $notification = Notification::with('user')->find($id);
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new NotificationResource($notification),
                'message' => 'Notification retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification',
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
            $notification = Notification::find($id);
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }
            $validator = Validator::make($request->all(), [
                'user_id' => 'nullable|exists:users,id',
                'admin_id' => 'nullable|exists:admins,id',
                'title' => 'sometimes|required|string|max:255',
                'body' => 'sometimes|required|string',
                'type' => 'sometimes|in:info,warning,success,error,custom',
                'status' => 'sometimes|in:unread,read,archived',
                'data' => 'nullable|array',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $notification->update($request->all());
            $notification->load('user');
            return response()->json([
                'success' => true,
                'data' => new NotificationResource($notification),
                'message' => 'Notification updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification',
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
            $notification = Notification::find($id);
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }
            $notification->delete();
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark the specified notification as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {
            $notification = Notification::find($id);
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }
            $notification->update(['status' => Notification::STATUS_READ]);
            $notification->load('user');
            return response()->json([
                'success' => true,
                'data' => new NotificationResource($notification),
                'message' => 'Notification marked as read'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archive the specified notification.
     */
    public function archive(string $id): JsonResponse
    {
        try {
            $notification = Notification::find($id);
            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }
            $notification->update(['status' => Notification::STATUS_ARCHIVED]);
            $notification->load('user');
            return response()->json([
                'success' => true,
                'data' => new NotificationResource($notification),
                'message' => 'Notification archived'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 