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
            ->withServiceAccount(storage_path('app/firebase/rideapp-ea087-firebase-adminsdk-fbsvc-59429f3026.json'));

        $this->messaging = $firebase->createMessaging();
    }

public function sendNotification($fcmToken, $title, $body)
{
    if (!$fcmToken) {
        throw new \Exception('User does not have a valid FCM token');
    }

    $notification = Notification::create($title, $body);
    $message = CloudMessage::withTarget('token', $fcmToken)
        ->withNotification($notification);

    $this->messaging->send($message);

    return 'Notification sent successfully.';
}

}
