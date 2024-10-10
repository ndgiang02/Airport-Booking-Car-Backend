<?php

use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\DriverController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\VehiclesController;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\ClusterController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\IntroController;


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

Route::post('register', [UserController::class,'registerCustomer']);

Route::post('register-driver', [DriverController::class, 'registerDriver1']);

Route::post('check-phone', [UserController::class, 'checkPhoneNumber']);
Route::post('get-user-by-phone', [UserController::class, 'getUserByPhoneNumber']);

Route::post('login', [UserController::class, 'login']);
Route::post('send-otp', [OtpController::class, 'sendOtp1']);
Route::post('reset-password-otp', [UserController::class, 'resetPasswordWithOtp']);

Route::group(['middleware' => ['auth:sanctum']], function () {
  
    Route::get('user-list', [UserController::class, 'userList']);
    Route::get('user-detail', [UserController::class, 'userDetail']);
    Route::post('update-profile', [UserController::class, 'updateProfile']);
    Route::post('change-password', [UserController::class, 'changePassword']);
    Route::post('update-user-status', [UserController::class, 'updateUserStatus']);
    Route::delete('delete-user-account', [UserController::class, 'deleteUserAccount']);

    Route::get('fetch-trips', [BookingController::class, 'getTrips']);
    Route::post('trip-booking', [BookingController::class, 'tripBooking']);
    Route::post('cancel-trip', [BookingController::class, 'cancelTrip']);

    Route::get('terms', [TermsController::class, 'getTerm']);
    Route::get('intro', [IntroController::class, 'getIntrodution']);

    Route::post('driver/update-status', [DriverController::class, 'updateStatus']);
    Route::post('driver/update-location', [DriverController::class, 'updateLocation']);
    Route::post('driver/accept-trip', [DriverController::class, 'acceptTrip']);
    Route::post('driver/start-trip', [DriverController::class, 'startTrip']);
    Route::post('driver/complete-trip', [DriverController::class, 'completeTrip']);
    Route::get('driver/my-wallet', [WalletController::class, 'getWalletDriver']);

    Route::get('vehicle/get-vehicle', [VehiclesController::class, 'getVehicleInfo']);
    Route::post('trip-cluster', [BookingController::class, 'TripCluster']);


    //Route::get('vehicle-types', [VehiclesController::class, 'getVehicleTypes']);
    //Route::post('notifications', [NotificationController::class, 'sendNotificationToAllUsers']);
    Route::post('logout', [UserController::class, 'logout']);
});
    Route::post('notifications', [NotificationController::class, 'sendNotificationToAllUsers']);

    Route::get('vehicle-types', [VehiclesController::class, 'getVehicleTypes']);

