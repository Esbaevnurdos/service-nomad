<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;

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

        $details = $this->getRideDetails(
            $request->pickup_lat,
            $request->pickup_lng,
            $request->dropoff_lat,
            $request->dropoff_lng
        );

        if ($details['error']) {
            return response()->json(['error' => $details['error']], 400);
        }

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

        $driver = User::findOrFail($request->driver_id);

        $order->update([
            'driver_id' => $driver->id,
            'driver_phone' => $driver->phone,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Order accepted successfully',
            'order' => $order,
            'passenger_phone' => $order->passenger_phone,
            'driver_phone' => $driver->phone,
            'driver_id' => $driver->id,
        ]);
    }


    public function cancelOrder(Request $request, $orderId) {
        $order = Order::findOrFail($orderId);

        if ($order->status === 'completed' || $order->status === 'canceled') {
            return response()->json(['error' => 'Order cannot be canceled'], 400);
        }

        $order->update(['status' => 'canceled']);

        return response()->json(['message' => 'Order canceled successfully', 'order' => $order]);
    }


   public function completeOrder($orderId)
{
    $order = Order::findOrFail($orderId);

    if ($order->status !== 'active') {
        return response()->json(['error' => 'Only active orders can be completed'], 400);
    }

    $order->update(['status' => 'completed']);

    return response()->json(['message' => 'Order completed successfully', 'order' => $order]);
}

public function getActiveOrder($orderId)
{
    $order = Order::where('id', $orderId)->where('status', 'active')->first();

    if (!$order) {
        return response()->json(['message' => 'No active order found'], 404);
    }

    return response()->json(['order' => $order]);
}




}
