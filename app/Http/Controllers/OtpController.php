<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use DB;

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
        $user->otp_expires_at = Carbon::now()->addMinutes(5);
        $user->save();

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json([
            'success' => 'Success',
            'message' => 'An OTP has been sent to your email address.',
            'status' => true
        ], 200);
    }

    public function sendOtp1(Request $request)
    {
        $request->validate([
            'email' => 'nullable|email',
            'mobile' => 'nullable|string',
            'otp_type' => 'required|in:login,reset_password',
        ]);

        $user = User::when($request->email, function ($query) use ($request) {
                return $query->where('email', $request->email);
            })
            ->when($request->mobile, function ($query) use ($request) {
                return $query->where('mobile', $request->mobile);
            })
            ->first();

        if (!$user) {
            return response()->json([
                'success' => 'Failed',
                'message' => 'The provided email or mobile number does not match our records.',
                'status' => false
            ], 400);
        }

        $otp = rand(1000, 9999);

        DB::table('otps')->insert([
            'user_id' => $user->id,
            'otp' => $otp,
            'otp_type' => $request->otp_type,
            'expires_at' => Carbon::now()->addMinutes(5),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        if ($request->email) {
            Mail::to($user->email)->send(new OtpMail($otp));
        }

        if ($request->mobile) {
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $twilioPhoneNumber = env('TWILIO_PHONE_NUMBER');

            $twilio = new Client($sid, $token);
            try {
                $twilio->messages->create($request->mobile, [
                    'from' => $twilioPhoneNumber,
                    'body' => "Your OTP code is: $otp"
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to send OTP via SMS',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'success' => 'Success',
            'message' => 'An OTP has been sent to your email or mobile number.',
            'status' => true
        ], 200);
    }


}
