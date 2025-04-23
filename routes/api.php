<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RideController;
use App\Http\Controllers\FirebaseNotificationController;
use App\Models\User;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::get('/rides/estimate', [RideController::class, 'estimateRide']);

Route::post('/rides/create', [RideController::class, 'createOrder']);

Route::post('/rides/{id}/accept', [RideController::class, 'acceptOrder']);
Route::post('/rides/{id}/cancel', [RideController::class, 'cancelOrder']);
Route::post('/rides/{id}/complete', [RideController::class, 'completeOrder']);
Route::get('/rides/{id}/active', [RideController::class, 'getActiveOrder']);

Route::post('/driver/update-location', [RideController::class, 'updateDriverLocation']);


Route::post('/send-user-notification', [FirebaseNotificationController::class, 'sendNotificationToUser']);


Route::post('/save-fcm-token', function (Request $request) {
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'fcm_token' => 'required'
    ]);

    $user = User::find($request->user_id);
    $user->fcm_token = $request->fcm_token;
    $user->save();

    return response()->json(['message' => 'FCM token saved successfully']);
});




/////////////////////////////////////
Route::post('/send-notification', [FirebaseNotificationController::class, 'send']);


Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});