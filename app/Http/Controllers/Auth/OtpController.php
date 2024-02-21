<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function store(Request $request)
    {
        // validate phone number
        $request->validate([
            'phone' => 'required|numeric|digits:13'
        ]);
        // check phone number exists or not in user model
        $phone = $request->phone;
        $user = User::where('phone', $phone)->first();
        if ($user) {
            // if exists then send otp to phone number
            $code = rand(1000, 9999);
            // save otp in otp model
            $otp = $user->otp()->create([
                'code' => $code,
                'expires_at' => now()->addDays(5)
            ]);
            // send otp to phone number
            $user->notify(new OtpNotification($otp));
            // return success message
            return response()->json(['message' => 'OTP sent successfully']);
        } else {
            // if not exists then return error message
            return response()->json(['message' => 'Phone number not exists'], 404);
        }
        // if exists then send otp to phone number
        // if not exists then return error message
    }

    public function login(Request $request)
    {
        // login using otp
        // validate phone number and otp
        $request->validate([
            'phone' => 'required|numeric|digits:13',
            'otp' => 'required|numeric|digits:4'
        ]);
        // check phone number exists or not in user model
        $phone = $request->phone;
        $user = User::where('phone', $phone)->first();

        if ($user) {
            // if exists then check otp
            $otp = $user->otp()->where('code', $request->otp)->where('expires_at', '>=', now())->first();
            if ($otp) {
                // if otp is valid then login user
                $user->tokens()->delete();
                $token = $user->createToken('otp')->plainTextToken;
                $otp->delete();
                return response()->json(['token' => $token]);
            } else {
                // if otp is invalid then return error message
                return response()->json(['message' => 'Invalid OTP'], 401);
            }
        } else {
            // if not exists then return error message
            return response()->json(['message' => 'Phone number not exists'], 404);
        }
    }
}
