<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Guest;
use App\Models\OutNotification;
use Illuminate\Http\Request;

class OutNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();
        $limit = $request->input('limit', 10);
        if (!$user) {
            if (!$request->fcm_token) {
                return response()->json(['message' => 'please enter fcm_token'], 401);
            }
            $guest = Guest::where('token', $request->fcm_token)->first();
            $notifications = OutNotification::whereJsonContains('guest_ids', $guest?->id)->latest()->paginate($limit);
            $notificationsResource = NotificationResource::collection($notifications);
            $counter = OutNotification::whereJsonContains('guest_ids', $guest?->id)->count();
        }else{
            $notifications = OutNotification::whereJsonContains('user_ids', $user->id)->latest()->paginate($limit);
            $notificationsResource = NotificationResource::collection($notifications);
            $counter = OutNotification::whereJsonContains('user_ids', $user->id)->where('seen', false)->count();
        }

        return [
            'data' => $notificationsResource,
            'counter' => $counter,
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'first_page_url' => $notifications->url(1),
                'last_page_url' => $notifications->url($notifications->lastPage()),
                'next_page_url' => $notifications->nextPageUrl(),
                'prev_page_url' => $notifications->previousPageUrl(),
            ],
        ];
    }

    public function show(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            if (!$request->fcm_token) {
                return response()->json(['message' => 'please enter fcm_token'], 401);
            }
        }
        $guest = Guest::where('token', $request->fcm_token)->first();
        $notification = OutNotification::whereJsonContains('user_ids', $user?->id)->where('id', $request->id)->first();
        $notificationForGuest = OutNotification::whereJsonContains('guest_ids', $guest?->id)->where('id', $request->id)->first();
        if (!$notification && $user) {
            return response()->json(['message' => 'Unauthorized To see this notification for user'], 401);
        }

        if (!$notificationForGuest && $guest) {
            return response()->json(['message' => 'Unauthorized To see this notification for guest'], 401);
        }
        if ($notificationForGuest) {
            return new NotificationResource($this->markAsRead($notificationForGuest->id));
        }
        return new NotificationResource($this->markAsRead($notification->id));
    }



    public function markAsRead($id)
    {
        $notification = OutNotification::findOrFail($id);
        $notification->update(['seen' => true]);
        return $notification;
    }



    public function getNotificationForGuest(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required'
        ]);

        $fcm_token = $request->input('fcm_token');
        $limit = $request->input('limit', 10);

        $guest = Guest::where('token', $fcm_token)->first();
        $notifications = OutNotification::whereJsonContains('guest_ids', $guest?->id)->latest()->paginate($limit);
        $counter = OutNotification::whereJsonContains('guest_ids', $guest?->id)->count();

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'counter' => $counter,
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'first_page_url' => $notifications->url(1),
                'last_page_url' => $notifications->url($notifications->lastPage()),
                'next_page_url' => $notifications->nextPageUrl(),
                'prev_page_url' => $notifications->previousPageUrl(),
            ],

        ]);
    }
}
