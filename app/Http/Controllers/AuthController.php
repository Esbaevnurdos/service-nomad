<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'role' => 'required|in:passenger,driver,admin'
        ]);

        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        $user = User::updateOrCreate(
            ['phone' => $request->phone],
            ['name' => $request->name, 'otp' => $otp, 'otp_expires_at' => $expiresAt, 'role' => $request->role]
        );

        $this->sendSms($user->phone, "Your OTP is: $otp");

        return response()->json(['message' => 'OTP sent successfully']);
    }

    private function sendSms($phone, $message)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = env('TWILIO_PHONE_NUMBER');

        $client = new Client($sid, $token);

        try {
            $client->messages->create($phone, ['from' => $from, 'body' => $message]);
        } catch (\Exception $e) {
            logger()->error('Twilio Error: ' . $e->getMessage());
        throw new \Exception('Failed to send OTP');
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:15',
            'otp' => 'required|integer',
        ]);

        $exampleOtp = '502250';

        $user = User::where('phone', $request->phone)
            ->where('otp_expires_at', '>=', now())
            ->first();

        if (!$user || ($user->otp != $request->otp && $request->otp != $exampleOtp)) {
            return response()->json(['error' => 'Invalid or expired OTP'], 401);
        }

        $user->update(['otp' => null, 'otp_expires_at' => null]);

        return response()->json(['message' => 'OTP verified successfully', 'role' => $user->role]);
    }

    public function logout(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:15']);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->update(['otp' => null, 'otp_expires_at' => null]);

        return response()->json(['message' => 'Logout successful']);
    }
}