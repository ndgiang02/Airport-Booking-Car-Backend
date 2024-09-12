<?php

use Illuminate\Routing\Router;
use App\Admin\Controllers\UserController;
use App\Admin\Controllers\DriverController;
use App\Admin\Controllers\VehicleController;
use App\Admin\Controllers\TripBookingController;


Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
    $router->resource('users', UserController::class);
    $router->resource('drivers', DriverController::class);
    $router->resource('vehicles', VehicleController::class);
    $router->resource('trip-bookings', TripBookingController::class);



});
