<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Driver;
use App\Http\Requests\UserRequest;
use App\Http\Requests\DriverRequest;
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
    public function register(UserRequest $request)
    {
        $input = $request->all();

        // Mã hóa mật khẩu
        $input['password'] = Hash::make($input['password']);
        $input['user_type'] = $input['user_type'] ?? 'customer';

        // Nếu là tài xế, đặt trạng thái thành 'pending'
        if ($input['user_type'] === 'driver') {
            $input['status'] = $input['status'] ?? 'pending';
        }

        $user = User::create($input);
        $user->assignRole($input['user_type']);  // Gán vai trò cho người dùng

        // Nếu có thông tin chi tiết người dùng, tạo record liên quan
        if ($request->has('user_detail')) {
            $user->userDetail()->create($request->user_detail);
        }

        // Trả về token API và phản hồi JSON
        $user->api_token = $user->createToken('auth_token')->plainTextToken;
        $response = [
            'message' => __('message.save_form', ['form' => __('message.' . $input['user_type'])]),
            'data' => new UserResource($user)
        ];

        return response()->json($response);
    }

    /**
     * Đăng ký tài xế
     */
    public function driverRegister(DriverRequest $request)
    {
        // Lấy tất cả dữ liệu từ request
        $input = $request->all();
    
        // Mã hóa password trước khi lưu
        $input['password'] = Hash::make($input['password']);
    
        // Xác định loại người dùng là 'driver'
        $input['user_type'] = 'driver';
        
        // Đặt trạng thái của tài xế mới là 'pending'
        $input['status'] = 'pending';
    
        // Mặc định tài xế là có sẵn (available)
        $input['is_available'] = true;
    
        // Tạo người dùng mới với dữ liệu đã nhập vào bảng users
        $user = User::create($input);
    
        // Tạo bản ghi cho bảng drivers với thông tin dành riêng cho tài xế
        $driver = Driver::create([
            'user_id' => $user->id,  // Liên kết với người dùng vừa tạo
            'license_no' => $input['license_no'],  // Số giấy phép lái xe
            'rating' => 5.0,  // Rating mặc định của tài xế là 5.0
            'available' => true,  // Tài xế có sẵn
            'vehicle_id' => $input['vehicle_id'],  // Phương tiện mà tài xế sử dụng
        ]);
    /*
        // Nếu có chi tiết người dùng và tài khoản ngân hàng, tạo các bản ghi liên quan
        if ($request->has('user_detail')) {
            $user->userDetail()->create($request->user_detail);
        }
        if ($request->has('user_bank_account')) {
            $user->userBankAccount()->create($request->user_bank_account);
        }
        
        // Tạo ví cho tài xế với số dư ban đầu là 0
        $user->userWallet()->create(['total_amount' => 0]);
        */
    
        // Tạo token cho người dùng sau khi đăng ký
        $user->api_token = $user->createToken('auth_token')->plainTextToken;
    
        // Chuẩn bị phản hồi dữ liệu sau khi đăng ký thành công
        $response = [
            'message' => __('message.save_form', ['form' => __('message.driver')]),
            'data' => new DriverResource($user)  // Trả về dữ liệu người dùng dưới dạng resource
        ];
    
        // Trả về phản hồi JSON
        return response()->json($response);
    }
    

    /**
     * Đăng nhập người dùng
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password', 'user_type');

        // Kiểm tra thông tin đăng nhập
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Kiểm tra xem tài khoản có bị khóa không
            if ($user->status === 'banned') {
                return response()->json(['message' => __('message.account_banned')], 400);
            }


            // Trả về token API và thông tin người dùng
            $user->api_token = $user->createToken('auth_token')->plainTextToken;
            $response = new UserResource($user);

            return response()->json(['data' => $response], 200);
        }

        return response()->json(['message' => __('auth.failed')], 400);
    }

    /**
     * Lấy danh sách người dùng
     */
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

    /**
     * Chi tiết người dùng
     */
    public function userDetail(Request $request)
    {
        $id = $request->id;

        $user = User::findOrFail($id);

        if ($user->user_type == 'driver') {
            return new DriverResource($user);
        }

        return new UserResource($user);
    }
    
    /**
     * Đổi mật khẩu người dùng
     */
    public function changePassword(Request $request)
    {
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

    /**
     * Cập nhật thông tin người dùng
     */
    public function updateProfile(UserRequest $request)
    {
        $user = Auth::user();
        $user->fill($request->all())->save();

        if ($request->has('profile_image')) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        if ($user->user_type == 'driver') {
            return new DriverResource($user);
        }

        return new UserResource($user);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if($request->is('api*')){
            $clear = request('clear');
            if( $clear != null ) {
                $user->$clear = null;
            }
            $user->save();
            return response()->json(['message' => __('message.log_out')], 200);
        }
    }
    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'mobile'=> 'required|mobile',
        ]);

        $response = Password::sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? response()->json(['message' => __($response), 'status' => true], 200)
            : response()->json(['message' => __($response), 'status' => false], 400);
    }
}
