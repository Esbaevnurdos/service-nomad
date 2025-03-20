<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RideController;

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