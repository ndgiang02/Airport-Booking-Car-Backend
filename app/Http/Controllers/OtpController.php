<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            /*
            'mobile' => [
                'required',
                'regex:/^(\+84|0)(3|5|7|8|9)[0-9]{8}$/'
            ],
            */
        ]);

        $user = User::where('email', $request->email)
            //->where('mobile', $request->mobile)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => 'Failed',
                'message' => 'The provided email or mobile number does not match our records.',
                'status' => false
            ], 400);
        }

        $otp = rand(1000, 9999);
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json([
            'success' => 'Success',
            'message' => 'An OTP has been sent to your email address.',
            'status' => true
        ], 200);
    }
}
