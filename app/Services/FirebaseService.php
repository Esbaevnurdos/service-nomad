<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $firebase = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'));

        $this->messaging = $firebase->createMessaging();
    }

    public function sendNotification($fcmToken, $title, $body)
    {
        if (!$fcmToken) {
            return response()->json(['error' => 'User does not have a valid FCM token'], 400);
        }

        $notification = Notification::create($title, $body);
        $message = CloudMessage::withTarget('token', $fcmToken)
            ->withNotification($notification);

        return $this->messaging->send($message);
    }
}
