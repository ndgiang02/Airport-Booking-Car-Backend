<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function resetPasswordWithOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
            'new_password' => 'required|string|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp != $request->otp || Carbon::now()->gt($user->otp_expires_at)) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
                'status' => false
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Password reset successfully.',
            'status' => true
        ], 200);
    }

}
