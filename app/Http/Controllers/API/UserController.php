<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Otp;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Driver;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserResource;
use App\Http\Resources\DriverResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\API\NotificationController;
use Carbon\Carbon;

class UserController extends Controller
{

    public function registerCustomer(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'mobile' => 'required|string|max:20|unique:users,mobile',
                'user_type' => 'required|in:customer',
            ]);

            $validatedData['password'] = Hash::make($validatedData['password']);
            $validatedData['status'] = 'active';

            $user = User::create($validatedData);

            $customer = Customer::create([
                'user_id' => $user->id,
                'rating' => 5.0,
            ]);

            return response()->json([
                'message' => 'Customer registered successfully',
                'data' => [
                    'user' => new UserResource($user),
                    'customer' => $customer
                ],
                'status' => true
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password', 'user_type');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->status === 'banned') {
                return response()->json(['message' => __('message.account_banned')], 403);
            }

            if ($request->has('device_token')) {
                if ($user->user_type === 'driver') {
                    $user->driver->device_token = $request->device_token;
                    $user->driver->save();
                } elseif ($user->user_type === 'customer') {
                    $user->customer->device_token = $request->device_token;
                    $user->customer->save();
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            if ($user->user_type === 'driver') {
                $user->driver->token = $user->token;
                $response = [
                    'user' => new UserResource($user),
                    'driver' => new DriverResource($user->driver->load('vehicle')),
                    'token' => $token
                ];
            } else {
                $response = [
                    'user' => new UserResource($user),
                    'token' => $token
                ];
            }

            if ($user->is_first_login && $user->user_type === 'customer') {
                $this->sendWelcomeNotification($user->customer->device_token);
                $user->is_first_login = false;
                $user->save();
            }

            return response()->json([
                'message' => __('login.succesful'),
                'status' => true,
                'data' => $response,
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => __('auth.failed')
        ], 401);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => __('message.valid_password')], 422);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return response()->json(['message' => __('message.old_new_pass_same')], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => __('message.password_change')
        ], 200);
    }

    /*

    public function updateProfile(UserRequest $request)
    {
        $user = Auth::user();

        if ($request->has('id') && !empty($request->id)) {
            $user = User::where('id', $request->id)->first();
        }

        if ($user == null) {
            return response()->json(['message' => __('message.no_record_found')], 400);
        }

        if ($user->user_type == 'customer') {
            $user->fill($request->only(['name', 'mobile']))->update();
        }

        if ($user->user_type == 'driver') {
            $user->fill($request->all())->update();

            if (isset($request->profile_image) && $request->profile_image != null) {
                $user->clearMediaCollection('profile_image');
                $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
            }

            if ($request->has('vehicle') && $request->vehicle != null) {
                $vehicle = $user->vehicle()->first();
                if ($vehicle) {
                    $vehicle->fill($request->vehicle)->update();
                } else {
                    $user->vehicle()->create($request->vehicle);
                }
            }
        }

        $user_data = User::find($user->id);

        // Cập nhật user detail nếu có
        if ($user_data->userDetail != null && $request->has('user_detail')) {
            $user_data->userDetail->fill($request->user_detail)->update();
        } else if ($request->has('user_detail') && $request->user_detail != null) {
            $user_data->userDetail()->create($request->user_detail);
        }

        // Chuẩn bị dữ liệu phản hồi
        $message = __('message.updated');
        unset($user_data['media']);

        // Chuẩn bị dữ liệu phản hồi cho tài xế hoặc khách hàng
        if ($user_data->user_type == 'driver') {
            $user_resource = new DriverResource($user_data);
        } else {
            $user_resource = new UserResource($user_data);
        }

        $response = [
            'data' => $user_resource,
            'message' => $message
        ];

        return response()->json($response);
    }
        */

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($request->is('api*')) {

            $request->user()->currentAccessToken()->delete();

            return response()->json(['message' => __('message.log_out')], 200);
        }

        return response()->json(['message' => 'Logout failed.'], 400);
    }


    public function deleteUserAccount(Request $request)
    {
        $id = auth()->id();
        $user = User::find($id);
        $message = __('message.not_found_entry', ['name' => __('message.account')]);

        if ($user) {
            if ($user->user_type === 'driver') {
                $driver = Driver::where('user_id', $user->id)->first();
                if ($driver) {
                    Vehicle::where('driver_id', $driver->id)->delete();
                    $driver->delete();
                }
            }

            if ($user->user_type === 'customer') {
                Customer::where('user_id', $user->id)->delete();
            }
            $user->delete();

            $message = __('message.account_deleted');
            return response()->json(['message' => $message, 'status' => true], 200);
        }
        return response()->json(['message' => $message, 'status' => false], 404);
    }

    public function resetPasswordWithOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
            'new_password' => 'required|string|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
                'status' => false
            ], 404);
        }

        $otpRecord = Otp::where('user_id', $user->id)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
                'status' => false
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $otpRecord->delete();
        $user->save();

        return response()->json([
            'message' => 'Password reset successfully.',
            'status' => true
        ], 200);
    }

    public function updateDeviceToken(Request $request)
    {
        $user = auth()->user();
        $user->device_token = $request->device_token;
        $user->save();
        return response()->json(['status' => 'success', 'message' => 'Device token updated successfully!']);
    }

    protected function sendWelcomeNotification($fcmToken)
    {
        $notificationData = [
            'notification' => [
                'title' => 'Xin chào!',
                'body' => 'Cảm ơn bạn đã tham gia dịch vụ của chúng tôi!',
            ],
            'data' => [
                'type' => 'welcome',
                'message' => 'Chào mừng bạn đến với dịch vụ của chúng tôi!',
            ],
        ];

        if ($fcmToken) {
            $notificationController = new NotificationController();
            $notificationController->sendNotification($fcmToken, $notificationData);
        } else {
            Log::error("Error sending welcome notification. FCM Token not found.");
        }
    }

    public function checkPhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|max:15',
            'user_type' => 'required|in:customer,driver',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $phoneNumber = $request->input('mobile');
        $userType = $request->input('user_type');

        $user = User::where('mobile', $phoneNumber)
            ->where('user_type', $userType)
            ->first();

        if ($user) {
            return response()->json([
                'status' => true,
                'message' => 'Phone number is already registered.',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Phone number is not registered.',
            ], 200);
        }
    }


    public function getUserByPhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string|max:15',
            'user_type' => 'required|in:customer,driver',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $phoneNumber = $request->input('mobile');
        $userType = $request->input('user_type');

        $user = User::where('mobile', $phoneNumber)
            ->where('user_type', $userType)
            ->first();

        if ($request->has('device_token')) {
            if ($user->user_type === 'driver') {
                $user->driver->device_token = $request->device_token;
                $user->driver->save();
            } elseif ($user->user_type === 'customer') {
                $user->customer->device_token = $request->device_token;
                $user->customer->save();
            }
        }

        if ($user) {
            $token = $user->createToken('authToken')->plainTextToken;

            if ($user->user_type === 'driver') {
                $response = [
                    'user' => new UserResource($user),
                    'driver' => new DriverResource($user->driver->load('vehicle')),
                    'token' => $token
                ];
            } else {
                $response = [
                    'user' => new UserResource($user),
                    'token' => $token
                ];
            }

            if ($user->is_first_login && $user->user_type === 'customer') {
                $this->sendWelcomeNotification($user->customer->device_token);
                $user->is_first_login = false;
                $user->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'User found.',
                'data' => $response,
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
            ], 404);
        }
    }


}
