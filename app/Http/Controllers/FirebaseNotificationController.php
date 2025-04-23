<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use App\Models\User;

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use App\Models\User;

class FirebaseNotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function notificationToDriver(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $driver = User::find($request->driver_id);

        if (!$driver->fcm_token || $driver->role !== 'driver') {
            return response()->json(['error' => 'Driver does not have an FCM token'], 400);
        }

        $this->firebaseService->sendNotification($driver->fcm_token, $request->title, $request->body);

        return response()->json(['message' => 'Notification sent to driver successfully']);
    }

    public function notificationToPassenger(Request $request)
    {
        $request->validate([
            'passenger_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $passenger = User::find($request->passenger_id);

        if (!$passenger->fcm_token || $passenger->role !== 'passenger') {
            return response()->json(['error' => 'Passenger does not have an FCM token'], 400);
        }

        $this->firebaseService->sendNotification($passenger->fcm_token, $request->title, $request->body);

        return response()->json(['message' => 'Notification sent to passenger successfully']);
    }

    public function notificationToPassengers(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $passengers = User::where('role', 'passenger')->get();

        foreach ($passengers as $passenger) {
            if ($passenger->fcm_token) {
                $this->firebaseService->sendNotification($passenger->fcm_token, $request->title, $request->body);
            }
        }

        return response()->json(['message' => 'Notifications sent to passengers']);
    }

    public function notificationToDrivers(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $drivers = User::where('role', 'driver')->get();

        foreach ($drivers as $driver) {
            if ($driver->fcm_token) {
                $this->firebaseService->sendNotification($driver->fcm_token, $request->title, $request->body);
            }
        }

        return response()->json(['message' => 'Notifications sent to all drivers']);
    }


    public function send(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $firebase = new FirebaseService();

        try {
            $result = $firebase->sendNotification(
                $request->device_token,
                $request->title,
                $request->body
            );

            return response()->json(['message' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    
}


