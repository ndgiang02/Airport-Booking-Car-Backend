<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// Đăng ký người dùng
Route::post('/register', [UserController::class, 'register']);

// Đăng ký tài xế
Route::post('/driver-register', [UserController::class, 'driverRegister']);

// Đăng nhập người dùng
Route::post('/login', [UserController::class, 'login']);

// Lấy danh sách người dùng (Yêu cầu xác thực)
Route::middleware('auth:sanctum')->get('/user-list', [UserController::class, 'userList']);

// Lấy thông tin chi tiết người dùng (Yêu cầu xác thực)
Route::middleware('auth:sanctum')->get('/user-detail', [UserController::class, 'userDetail']);

// Đổi mật khẩu người dùng (Yêu cầu xác thực)
Route::middleware('auth:sanctum')->post('/change-password', [UserController::class, 'changePassword']);

// Cập nhật thông tin người dùng (Yêu cầu xác thực)
Route::middleware('auth:sanctum')->post('/update-profile', [UserController::class, 'updateProfile']);

// Đăng xuất (Yêu cầu xác thực)
Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);

// Quên mật khẩu
Route::post('/forgot-password', [UserController::class, 'forgetPassword']);