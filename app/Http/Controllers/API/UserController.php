<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Driver;
use App\Http\Resources\UserResource;
use App\Http\Resources\DriverResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserController extends Controller
{

    
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'mobile' => 'nullable|string|max:20|unique:users,mobile',
            'user_type' => 'required|in:customer,driver',
            'license_no' => 'required_if:user_type,driver|string|max:255',
            'vehicle_type' => 'required_if:user_type,driver|string|max:255',
            'license_plate' => 'required_if:user_type,driver|string|max:255|unique:vehicles,license_plate',
            'seating_capacity' => 'required_if:user_type,driver|integer',
            'brand' => 'required_if:user_type,driver|string|max:50',
            'color' => 'required_if:user_type,driver|string|max:30',
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);

        $validatedData['status'] = $validatedData['user_type'] === 'customer' ? 'active' : 'pending';

        $user = User::create($validatedData);

        if ($validatedData['user_type'] === 'customer') {

            $customer = Customer::create([
                'user_id' => $user->id,
                'rating' => 5.0,
            ]);

            $data = [
                'user' => new UserResource($user),
                'customer' => $customer
            ];

        } elseif ($validatedData['user_type'] === 'driver') {
            $driver = Driver::create([
                'user_id' => $user->id,
                'license_no' => $validatedData['license_no'],
                'rating' => 5.0,
                'available' => false,
            ]);

            $vehicleType = $validatedData['vehicle_type'];
            $vehicleConfig = config("vehicle.types.$vehicleType");

            if (!$vehicleConfig) {
                return response()->json(['error' => 'Invalid vehicle type'], 400);
            }

            $startingPrice = $vehicleConfig['starting_price'];
            $ratePerKm = $vehicleConfig['rate_per_km'];

            $vehicle = Vehicle::create([
                'driver_id' => $driver->id,
                'vehicle_type' => $vehicleType,
                'initial_starting_price' => $startingPrice,
                'rate_per_km' => $ratePerKm,
                'license_plate' => $validatedData['license_plate'],
                'seating_capacity' => $validatedData['seating_capacity'],
                'brand' => $validatedData['brand'],
                'color' => $validatedData['color'],
            ]);

            $data = [
                'user' => new UserResource($user),
                'driver' => new DriverResource($driver->load('vehicle'))
            ];
        }

        $user->token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'data' => $data,
            'status' => true
        ], 201);
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

                $this->sendDeviceTokenNotification($user, $request->device_token);
            }

            $user->token = $user->createToken('auth_token')->plainTextToken;
            if ($user->user_type === 'driver') {
                $user->driver->token = $user->token;
                $response = new DriverResource($user->driver->load('vehicle'));
            } else {
                $response = new UserResource($user);
            }
            return response()->json([
                'data' => $response,
                'status' => true,
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => __('auth.failed')
        ], 401);
    }

    public function userList(Request $request)
    {
        $user_type = $request->get('user_type', 'customer');

        $user_list = User::where('user_type', $user_type);

        if ($request->has('is_online')) {
            $user_list->where('is_online', $request->get('is_online'));
        }

        if ($request->has('status')) {
            $user_list->where('status', $request->get('status'));
        }

        $users = $user_list->paginate(config('constant.PER_PAGE_LIMIT', 15));

        return UserResource::collection($users);
    }

    public function userDetail(Request $request)
    {
        $id = $request->id;

        $user = User::findOrFail($id);

        if ($user->user_type == 'driver') {
            return new DriverResource($user);
        }

        return new UserResource($user);
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
            $user->fill($request->only(['name', 'phone_number']))->update();
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

    public function updateDeviceToken(Request $request)
    {
        $user = auth()->user();
        $user->device_token = $request->device_token;
        $user->save();
        return response()->json(['status' => 'success', 'message' => 'Device token updated successfully!']);
    }

}
