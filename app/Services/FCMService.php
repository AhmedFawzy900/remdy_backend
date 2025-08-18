<?php

namespace App\Services;

use GuzzleHttp\Client;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FCMService
{
    protected $messaging;

    // Constructor Dependency Injection
    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendNotification($deviceToken, $title, $body, $image, $type = null, $id)
    {
        // Create the notification
        $notification = FcmNotification::create($title, $body, $image);

        $data = [
            'type' => $type,
            'id' => $id,
            'image' => $image
        ];
        

        // Create the cloud message
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification)->withData($data); // Attach the data payload
       
        try {
            
            // Ensure $this->messaging is not null
            if ($this->messaging) {
                $this->messaging->send($message);
            } else {
              
                \Log::error('Messaging service is not initialized.');
            }
        } catch (\Exception $e) {
            // dd($e);
            \Log::error('Firebase Notification Error: ' . $e->getMessage());
        }
    }
}