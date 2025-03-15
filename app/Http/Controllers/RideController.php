<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        $pickup = $request->pickup_lat . ',' . $request->pickup_lng;
        $dropoff = $request->dropoff_lat . ',' . $request->dropoff_lng;

        $apiKey = env("GOOGLE_MAPS_API_KEY");

        $response = Http::get("https://maps.googleapis.com/maps/api/distancematrix/json", [
            'origins' => $pickup,
            'destinations' => $dropoff,
            'key' => $apiKey,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to connect to Google API'], 500);
        }

        $data = $response->json();

 
        if (
            !isset($data['rows'][0]['elements'][0]['distance']['value']) ||
            $data['rows'][0]['elements'][0]['status'] !== "OK"
        ) {
            return response()->json(['error' => $data['error_message'] ?? 'Unable to calculate distance'], 400);
        }

        $distance = $data['rows'][0]['elements'][0]['distance']['value'] / 1000; 
        $duration = $data['rows'][0]['elements'][0]['duration']['value'] / 60; 

        $cost = $this->calculateFare($distance, $duration);

        return response()->json([
            'distance_km' => $distance,
            'duration_min' => $duration,
            'estimated_fare' => $cost
        ]);
    }

    private function calculateFare($distance, $duration)
    {
        $baseFare = 500; 
        $costPerKm = 100; 
        $costPerMin = 50; 

        return $baseFare + ($distance * $costPerKm) + ($duration * $costPerMin);
    }
}
