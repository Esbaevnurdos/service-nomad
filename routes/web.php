<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;


Route::get('/', function () {
    return view('welcome');
});


// use Twilio\Rest\Client;

// Route::get('/twilio-test', function () {
//     $sid = env('TWILIO_SID');
//     $token = env('TWILIO_AUTH_TOKEN');
//     $from = env('TWILIO_PHONE_NUMBER');
//     $to = '+77766393165'; // Replace with your phone number

//     try {
//         $twilio = new Client($sid, $token);
//         $twilio->messages->create($to, [
//             'from' => $from,
//             'body' => 'Hello from Laravel!',
//         ]);
//         return 'Message sent successfully!';
//     } catch (\Exception $e) {
//         return 'Error: ' . $e->getMessage();
//     }
// });
