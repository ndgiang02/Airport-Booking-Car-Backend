<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Driver;
use App\Http\Resources\UserResource;
use App\Http\Resources\DriverResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    /**
     * Đăng ký người dùng
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'user_type' => 'nullable|in:customer,driver',
            /*
            'mobile' => [
                'required',
                'regex:/^(\+84|0)(3|5|7|8|9)[0-9]{8}$/'
            ],
            */
            'mobile' => 'nullable|string|max:20|unique:users,mobile',
            'license_no' => 'nullable|string|max:255',
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);
        if ($validatedData['user_type'] === 'driver') {
            $validatedData['status'] = 'pending';
            $validatedData['is_available'] = true;
        }

        $user = User::create($validatedData);

        $user->assignRole($validatedData['user_type']);

        if ($validatedData['user_type'] === 'driver') {
            Driver::create([
                'user_id' => $user->id,
                'license_no' => $validatedData['license_no'],
                'rating' => 5.0,
                'available' => false,
            ]);

            // Nếu có chi tiết người dùng và tài khoản ngân hàng, có thể thêm sau
            /*
            if ($request->has('user_detail')) {
                $user->userDetail()->create($request->user_detail);
            }
            if ($request->has('user_bank_account')) {
                $user->userBankAccount()->create($request->user_bank_account);
            }
            // Tạo ví cho tài xế với số dư ban đầu là 0
            $user->userWallet()->create(['total_amount' => 0]);
            */
        }

        if ($validatedData['user_type'] === 'customer') {
            Customer::create([
                'user_id' => $user->id,
                'rating' => 5.0,
            ]);
        }
        $user->token = $user->createToken('auth_token')->plainTextToken;
        $response = [
            'message' => __('message.save_form', ['form' => __('message.' . $validatedData['user_type'])]),
            'data' => new UserResource($user)
        ];

        return response()->json($response);
    }


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password', 'user_type');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->status === 'banned') {
                return response()->json(['message' => __('message.account_banned')], 400);
            }

            $user->token = $user->createToken('auth_token')->plainTextToken;
            $response = new UserResource($user);

            return response()->json(['data' => $response], 200);
        }

        return response()->json(['message' => __('auth.failed')], 400);
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
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => __('message.valid_password')], 400);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return response()->json(['message' => __('message.old_new_pass_same')], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => __('message.password_change')], 200);
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
            $user->delete();
            $message = __('message.account_deleted');
            return response()->json(['message' => $message, 'status' => true], 200);
        }

        return response()->json(['message' => $message, 'status' => false], 404);
    }


}
