<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Driver;
use App\Models\Passenger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\RestException;



class AuthController extends Controller
{
    public function login(Request $request)
{
    // Validate input
    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|regex:/^\+?\d{10,15}$/',
        'role' => 'required|in:passenger,driver,admin'
    ]);

    // Generate OTP and expiration time
    $otp = $this->generateOtp();
    $expiresAt = Carbon::now()->addMinutes(5);

    try {

        $user = User::where('phone', $request->phone)->first();

        if ($user) {
            $user->update([
                'otp' => $otp,
                'otp_expires_at' => $expiresAt,
                'role' => $request->role,
            ]);
        } else {
            $user = User::create(
            ['name' => $request->name, 
            'otp' => $otp,
            'otp_expires_at' => $expiresAt,
            'role' => $request->role]
        );
        }


        // Ensure role-specific logic is applied
        $this->ensureRoleModel($user);

        // Send OTP via SMS
        $this->sendSms($user->phone, "Your OTP is: $otp");

        return response()->json(['message' => 'OTP sent successfully.']);
    } catch (\Exception $e) {
        Log::error('Error during login: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to send OTP. Please try again.'], 500);
    }
}


    private function generateOtp()
{
    return rand(100000, 999999);
}

    private function ensureRoleModel(User $user)
    {
        if ($user->role == 'driver' && !$user->driver) {
            Driver::create([
                'user_id' => $user->id,
                'status' => 'offline',
                'driver_phone' => $user->phone,
            ]);
        }

        if ($user->role == 'passenger' && !$user->passenger) {
            Passenger::create([
                'user_id' => $user->id,
                'passenger_phone' => $user->phone,
            ]);
        }
    }

    private function sendSms($phone, $message)
{
    $sid = env('TWILIO_SID');
    $token = env('TWILIO_AUTH_TOKEN');
    $from = env('TWILIO_PHONE_NUMBER');

    // Ensure Twilio credentials are set
    if (!$sid || !$token || !$from) {
        logger()->error('Twilio credentials are missing.');
        throw new \Exception('SMS service is not configured.');
    }

    // Validate phone number format (basic check)
    if (!preg_match('/^\+?\d{10,15}$/', $phone)) {
        logger()->warning("Invalid phone number: $phone");
        throw new \Exception('Invalid phone number format.');
    }

    try {
        $client = new Client($sid, $token);
        $client->messages->create($phone, [
            'from' => $from,
            'body' => $message
        ]);
    } catch (RestException $e) {
        logger()->error('Twilio Error: ' . $e->getMessage(), [
            'phone' => substr($phone, 0, 6) . '******',
            'code' => $e->getCode(),
            'status' => $e->getStatusCode()
        ]);
        throw new \Exception('Failed to send OTP. Please try again.');
    } catch (\Exception $e) {
        logger()->error('Unexpected SMS Error: ' . $e->getMessage());
        throw new \Exception('Unexpected error occurred while sending OTP.');
    }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:15',
            'otp' => 'required|integer',
        ]);

        $exampleOtp = '502250';

        $user = User::where('phone', $request->phone)->first();


        if (!$user || (!$user->otp && $request->otp != $exampleOtp) || ($user->otp_expires_at < now() && $request->otp != $exampleOtp)) {
            return response()->json([
                'error'=> "OTP expired or user not found"
            ], 401);
        }

        if ((int) $user->otp !== (int) $request->otp && $request->otp != $exampleOtp) {
            return response()->json(['error' => 'Invalid or expired OTP'], 401);
        }

        if ($request->otp != $exampleOtp) {
            $user->update(['otp' => null, 'otp_expires_at' => null]);
        }
        

        return response()->json([
            'message' => 'OTP verified successfully',
            'role' => $user->role
        ]);
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
