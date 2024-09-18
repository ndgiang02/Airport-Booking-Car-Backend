<?php

use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\DriverController;
use App\Http\Controllers\API\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ResetPasswordController;
use App\Http\Controllers\OtpController;

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

Route::post('register', [UserController::class, 'register']);

Route::post('login', [UserController::class, 'login']);

Route::post('send-otp', [OtpController::class, 'sendOtp']);

Route::post('reset-password-otp', [ResetPasswordController::class, 'resetPasswordWithOtp']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('update-driver-location', [DriverController::class, 'updateDriverLocation']);
    Route::post('create-booking', [BookingController::class, 'createBooking']);
    Route::get('user-list', [UserController::class, 'userList']);
    Route::get('user-detail', [UserController::class, 'userDetail']);
    Route::post('update-profile', [UserController::class, 'updateProfile']);
    Route::post('change-password', [UserController::class, 'changePassword']);
    Route::post('update-user-status', [UserController::class, 'updateUserStatus']);
    Route::delete('delete-user-account', [UserController::class, 'deleteUserAccount']);
    //Route::post('notifications', [NotificationController::class, 'sendNotificationToAllUsers']);
    Route::post('logout', [UserController::class, 'logout']);
});
    Route::post('notifications', [NotificationController::class, 'sendNotificationToAllUsers']);
