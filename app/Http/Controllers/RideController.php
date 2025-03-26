<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Driver;


class RideController extends Controller
{
    public function estimateRide(Request $request)
    {
        $request->validate([
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'dropoff_lat' => 'required|numeric',
            'dropoff_lng' => 'required|numeric',
        ]);

        $details = $this->getRideDetails(
            $request->pickup_lat,
            $request->pickup_lng,
            $request->dropoff_lat,
            $request->dropoff_lng
        );

        if ($details['error']) {
            return response()->json(['error' => $details['error']], 400);
        }

        return response()->json([
            'distance_km' => $details['distance'],
            'duration_min' => $details['duration'],
            'estimated_fare' => $details['fare'],
        ]);
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'passenger_id' => 'required|exists:users,id',
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'dropoff_lat' => 'required|numeric',
            'dropoff_lng' => 'required|numeric',
        ]);

        $passenger = User::findOrFail($request->passenger_id);

        if (Order::where('passenger_id', $passenger->id)->where('status', 'pending')->exists()) {
            return response()->json(['error' => 'You already have a pending ride'], 400);
        }

        $details = $this->getRideDetails(
            $request->pickup_lat,
            $request->pickup_lng,
            $request->dropoff_lat,
            $request->dropoff_lng
        );

        if ($details['error']) {
            return response()->json(['error' => $details['error']], 400);
        }

        $nearbyDrivers = $this->getNearbyDrivers($request->pickup_lat, $request->pickup_lng );
        if ($nearbyDrivers->isEmpty()) {
            return response()->json(['error' => 'No drivers available nearby'], 400);
        }

        $passenger->update([
            'pickup_lat' => $request->pickup_lat,
            'pickup_lng' => $request->pickup_lng,
        ]);

        $order = Order::create([
            'passenger_id' => $passenger->id,
            'pickup_lat' => $request->pickup_lat,
            'pickup_lng' => $request->pickup_lng,
            'dropoff_lat' => $request->dropoff_lat,
            'dropoff_lng' => $request->dropoff_lng,
            'distance_km' => $details['distance'],
            'duration_min' => $details['duration'],
            'fare' => $details['fare'],
            'status' => 'pending',
            'passenger_phone' => $passenger->phone,
        ]);
        
            $this->notificationToDrivers($order);


        return response()->json(['message' => 'Order created successfully', 'order' => $order], 201);
    }

    private function getRideDetails($pickupLat, $pickupLng, $dropoffLat, $dropoffLng)
    {
        $pickup = "{$pickupLat},{$pickupLng}";
        $dropoff = "{$dropoffLat},{$dropoffLng}";
        $apiKey = env('GOOGLE_MAPS_API_KEY');

        $response = Http::get("https://maps.googleapis.com/maps/api/distancematrix/json", [
            'origins' => $pickup,
            'destinations' => $dropoff,
            'key' => $apiKey,
        ]);

        if ($response->failed()) {
            return ['error' => 'Failed to connect to Google API'];
        }

        $data = $response->json();

        if (empty($data['rows'][0]['elements'][0]['distance']['value'])) {
            return ['error' => 'Unable to calculate distance'];
        }

        $distance = $data['rows'][0]['elements'][0]['distance']['value'] / 1000;
        $duration = $data['rows'][0]['elements'][0]['duration']['value'] / 60;

        $fare = $this->calculateFare($distance, $duration);

        return ['error' => null, 'distance' => $distance, 'duration' => $duration, 'fare' => $fare];
    }

    private function calculateFare($distance, $duration)
    {
        $baseFare = 500;
        $costPerKm = 100;
        $costPerMin = 50;

        return $baseFare + ($distance * $costPerKm) + ($duration * $costPerMin);
    }




    public function acceptOrder(Request $request, $orderId) {
        $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $order = Order::findorFail($orderId);

        if ($order->status !== 'pending') {
            return response()->json(['error' => 'Order is not available for acceptance'], 400);
        }

         $driver = User::where('id', $request->driver_id)
        ->where('role', 'driver')
        ->firstOrFail();

        $driverDetails = Driver::where('user_id', $driver->id)->first();

    //     if (!$driver->latitude || !$driver->longitude || !$driverDetails) {
    //     return response()->json(['error' => 'Driver location not available'], 400);
    // }

        if (!$driver->is_available || $driverDetails->status !== 'active') {
        return response()->json(['error' => 'Driver is not available for acceptance'], 400);
    }

    $rideDetails = $this->getRideDetails(
        $driver->latitude, $driver->longitude,
        $order->pickup_lat, $order->pickup_lng
    );
    if ($rideDetails['error']) {
        return response()->json(['error' => $rideDetails['error']], 500);
    }

        $order->update([
            'driver_id' => $driver->id,
            'driver_phone' => $driver->phone,
            'status' => 'active',
        ]);

        $driverDetails->update(['status' => 'in_ride']);
        $driver->update(['is_available' => false]);

        $this->sendNotification($order->passenger_phone, "Driver is on the way! {$rideDetails['duration']} minutes.");

        return response()->json([
            'message' => 'Order accepted successfully',
            'order' => $order,
            'passenger_phone' => $order->passenger_phone,
            'driver_phone' => $driver->phone,
            'driver_id' => $driver->id,
            'minutes' => $rideDetails['duration'],
        ]);
    }


public function cancelOrder(Request $request, $orderId) {
    $order = Order::findOrFail($orderId);

    if ($order->status === 'completed' || $order->status === 'canceled') {
        return response()->json(['error' => 'Order cannot be canceled'], 400);
    }

    // If a driver is assigned, reset their availability
    if ($order->driver_id) {
        $driver = User::find($order->driver_id);
        if ($driver) {
            $driver->update(['is_available' => true]);
            Driver::where('user_id', $driver->id)->update(['status' => 'active']);
        }
    }

    $order->update(['status' => 'canceled']);
    Log::info("Order #{$order->id} canceled successfully.");

    $this->notificationToPassenger($order->passenger_phone, "Your ride has been canceled.");
    if ($order->driver_phone) {
        $this->notificationToDriver($order->driver_phone, "The ride has been canceled.");
    }

    return response()->json(['message' => 'Order canceled successfully', 'order' => $order]);
}

public function completeOrder($orderId) {
    $order = Order::findOrFail($orderId);

    if ($order->status !== 'active') {
        return response()->json(['error' => 'Only active orders can be completed'], 400);
    }

    // Update driver status if applicable
    if ($order->driver_id) {
        $driver = User::find($order->driver_id);
        if ($driver) {
            $driver->update(['is_available' => true]);
            Driver::where('user_id', $driver->id)->update(['status' => 'active']);
        }
    }

    $order->update(['status' => 'completed']);
    Log::info("Order #{$order->id} completed successfully.");

    $this->notificationToPassenger($order->passenger_phone, "Your ride is complete. Thank you!");
    $this->notificationToDriver($order->driver_phone, "Ride completed successfully.");

    return response()->json(['message' => 'Order completed successfully', 'order' => $order]);
}

public function getActiveOrder($orderId) {
    $order = Order::where('id', $orderId)
                  ->where('status', 'active')
                  ->with(['passenger', 'driver'])
                  ->first();

    if (!$order) {
        return response()->json(['message' => 'No active order found'], 404);
    }

    return response()->json([
        'order' => $order,
        'driver' => $order->driver ? [
            'id' => $order->driver->id,
            'phone' => $order->driver->phone,
            'name' => $order->driver->name,
        ] : null,
        'passenger' => [
            'id' => $order->passenger->id,
            'phone' => $order->passenger->phone,
            'name' => $order->passenger->name,
        ],
    ]);
}


// public function getActiveOrder($orderId)
// {
//     $order = Order::where('id', $orderId)->where('status', 'active')->first();

//     if (!$order) {
//         return response()->json(['message' => 'No active order found'], 404);
//     }

//     return response()->json(['order' => $order]);
// }


private function sendNotification($phoneNumber, $message) {
    $response = Http::post(env('NOTIFICATION_API_URL'), [
        'to' => $phoneNumber,
        'message' => $message,
        'api_key' => env('NOTIFICATION_API_KEY'),
    ]);

    if ($response->failed()) {
        Log::error('Notification failed', ['phone' => $phoneNumber, 'message' => $message, 'response' => $response->body()]);
    }
}

 public function notificationToPassenger($phoneNumber, $message)
    {
        $this->sendNotification($phoneNumber, $message);
    }

    public function notificationToDriver($phoneNumber, $message)
    {
        $this->sendNotification($phoneNumber, $message);
    }

        public function notificationToDrivers(Order $order, $message = null)
    {
        $message = $message ?? "New ride request available. Pickup Location: ({$order->pickup_lat}, {$order->pickup_lng})";

        $nearbyDrivers = $this->getNearbyDrivers($order->pickup_lat, $order->pickup_lng);

        foreach ($nearbyDrivers as $driver) {
            $this->notificationToDriver($driver->phone, $message);
        }
    }

       private function getNearbyDrivers($lat, $lng)
    {
        return User::where('role', 'driver')
            ->where('is_available', true)
            ->where('latitude', '!=', null)
            ->where('longitude', '!=', null)
            ->whereRaw("ST_Distance_Sphere(point(longitude, latitude), point(?, ?)) <= 5000", [
                $lng, $lat
            ])->get();
    }


}
