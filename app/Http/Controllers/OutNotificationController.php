<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\OutNotification;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Http\Request;

class OutNotificationController extends Controller
{
    protected $notificationService;
    public function __construct(FCMService $notificationService)
    {
        $this->notificationService = $notificationService;
       
    }
    public function sendNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable',
            // 'action_url' => 'nullable|string|max:255',
            'type' => 'required|string|in:guest,user',
        ]);
        $fcm_tokens = [];
        $user_ids = [];
        $guest_ids = [];
        
        $notification = new OutNotification();
        // Set the validated data
        $notification->title = $request->title;

        $notification->description = $request->description;

        if ($request->type == 'guest') {
            $data = Guest::filterForNotification([]);
            // dd($data);
            foreach ($data as $guest) {
                if (!empty($guest['fcm_tokens'])) {
                    $fcm_tokens = array_merge($fcm_tokens, $guest['fcm_tokens']);
                }
                $guest_ids[] = $guest['id'];
            }
            // Store the IDs of companies or users as JSON
            if (!empty($guest_ids)) {
                $notification->guest_ids = json_encode($guest_ids);
            }
        }else{
            $data = User::filterForNotification([]);
            foreach ($data as $user) {
                $user_ids[] = $user['id'];
                if (!empty($user['fcm_tokens'])) {
                    $fcm_tokens = array_merge($fcm_tokens, $user['fcm_tokens']);
                }
            }
            // Store the IDs of companies or users as JSON
            if (!empty($user_ids)) {
                $notification->user_ids = json_encode($user_ids);
                // $notification->fcm_tokens = json_encode($fcm_tokens);
            }

        }
        $notification->action_url = $request->action_url;
        $notification->type = $request->type;
        $notification->image = $request->image;
        // dd($data);
        $notification->save();
        foreach ($fcm_tokens as $token) {
            $title = strip_tags($notification->title);
            $description = strip_tags($notification->description);
            
          
            $this->notificationService->sendNotification($token['token'], $title, $description, $notification->image, $notification->type, $notification->id);
            // $this->notificationService->sendNotification($token->token, "hallo", "test 2 for notification", null, null, "notification");
        }
        return response()->json(['message' => 'Notification sent successfully'], 200);
    }
}
