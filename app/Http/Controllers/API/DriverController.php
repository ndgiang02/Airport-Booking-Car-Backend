<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\WalletTransaction;
use App\Http\Resources\TripBookingResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\DriverResource;
use Illuminate\Support\Facades\Hash;
use App\Models\TripBooking;
use Illuminate\Http\Request;
use App\Events\DriverLocationUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use DB;

class DriverController extends Controller
{

	public function registerDriver(Request $request)
	{
		$validatedData = $request->validate([
			'name' => 'required|string|max:255',
			'email' => 'required|email|unique:users,email',
			'password' => 'required|min:8',
			'mobile' => 'nullable|string|max:20|unique:users,mobile',
			'user_type' => 'required|in:customer,driver',
			'license_no' => 'required_if:user_type,driver|string|max:255',
			'vehicle_type_id' => 'required_if:user_type,driver|integer|exists:vehicle_types,id',
			'license_plate' => 'required_if:user_type,driver|string|max:255|unique:vehicles,license_plate',
			'brand' => 'required_if:user_type,driver|string|max:50',
			'model' => 'required_if:user_type,driver|string|max:50',
			'color' => 'required_if:user_type,driver|string|max:30',
		]);

		$validatedData['password'] = Hash::make($validatedData['password']);
		$validatedData['status'] = 'active';

		$user = User::create($validatedData);

		$driver = Driver::create([
			'user_id' => $user->id,
			'license_no' => $validatedData['license_no'],
			'rating' => 5.0,
			'available' => false,
			'income' => 0.00,
			'wallet_balance' => 0.00,
		]);

		$vehicle = Vehicle::create([
			'driver_id' => $driver->id,
			'vehicle_type_id' => $validatedData['vehicle_type_id'],
			'license_plate' => $validatedData['license_plate'],
			'brand' => $validatedData['brand'],
			'color' => $validatedData['color'],
			'model' => $validatedData['model'],
		]);

		$user->token = $user->createToken('auth_token')->plainTextToken;

		$data = [
			'user' => new UserResource($user),
			'token' => $user->token,
			'driver' => new DriverResource($driver->load('vehicle'))
		];

		return response()->json(
			[
				'message' => "Register successful",
				'status' => true,
				'data' => $data
			],
			201
		);
	}

	public function registerDriver1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'mobile' => 'nullable|string|max:20|unique:users,mobile',
            'user_type' => 'required|in:customer,driver',
            'license_no' => 'required_if:user_type,driver|string|max:50|unique:drivers,license_no',
            'vehicle_type_id' => 'required_if:user_type,driver|integer|exists:vehicle_types,id',
            'license_plate' => 'required_if:user_type,driver|string|max:255|unique:vehicles,license_plate',
            'brand' => 'required_if:user_type,driver|string|max:50',
            'model' => 'required_if:user_type,driver|string|max:50',
            'color' => 'required_if:user_type,driver|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'mobile' => $request->mobile,
                'user_type' => $request->user_type,
            ]);

            if ($request->user_type === 'driver') {
                $driver = Driver::create([
                    'user_id' => $user->id,
                    'license_no' => $request->license_no,
                    'rating' => 5.0, 
                    'available' => false,
                    'income' => 0.00,
                    'wallet_balance' => 0.00, 
                ]);

                $vehicle = Vehicle::create([
                    'driver_id' => $driver->id,
                    'vehicle_type_id' => $request->vehicle_type_id,
                    'license_plate' => $request->license_plate,
                    'brand' => $request->brand,
                    'model' => $request->model,
                    'color' => $request->color,
                ]);
            }

            $user->token = $user->createToken('auth_token')->plainTextToken;

            $data = [
                'user' => new UserResource($user),
                'token' => $user->token,
                'driver' => new DriverResource($driver->load('vehicle'))
            ];

            DB::commit();

            return response()->json(
                [
                    'message' => "Register successful",
                    'status' => true,
                    'data' => $data
                ],
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


	/**
	 *update status
	 */
	public function updateStatus(Request $request)
	{
		$request->validate([
			'available' => 'required|boolean',
		]);

		$user = auth()->user();
		$driver = Driver::where('user_id', $user->id)->first();

		if ($driver) {
			$driver->available = $request->available;
			$driver->save();

			broadcast(new DriverLocationUpdated($driver->id, $driver->latitude, $driver->longitude));

			return response()->json([
				'status' => true,
				'message' => 'successfully.',
				'data' => [
					'available' => $driver->available
				]
			], 200);
		}
		return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
	}

	public function updateLocation(Request $request)
	{

		Log::info("Received request to update location: ", ['available' => $request->input('available')]);
		$request->validate([
			'latitude' => 'required|numeric',
			'longitude' => 'required|numeric',
		]);

		$user = auth()->user();
		$driver = Driver::where('user_id', $user->id)->first();

		if ($driver) {
			$driver->latitude = $request->latitude;
			$driver->longitude = $request->longitude;
			$driver->save();
			Log::info("Received request to update new location: ", ['latitude' => $driver->latitude, 'longitude' => $driver->longitude]);
			//broadcast(new DriverLocationUpdated($driver->id, $driver->latitude, $driver->longitude));
			return response()->json([
				'status' => true,
				'message' => 'successfully.',
				'data' => [
					'driver' => $driver
				]
			], 200);
		}
		return response()->json([
			'success' => false,
			'message' => 'fail.',
		], 401);
	}

	public function acceptTrip(Request $request)
	{

		$user = auth()->user();
		$driver = Driver::where('user_id', $user->id)->first();


		$tripId = $request->input('trip_id');

		$trip = TripBooking::with(['customer.user', 'stops'])
			->find($tripId);

		if (!$trip) {
			return response()->json(['error' => 'Trip not found'], 404);
		}

		if ($trip->status === 'accepted') {
			return response()->json(['message' => 'Trip already accepted by another driver'], 409);
		}

		try {
			DB::beginTransaction();

			$updated = TripBooking::where('id', $tripId)
				->where('trip_status', 'requested')
				->update(['trip_status' => 'accepted', 'driver_id' => $driver->id]);

			if (!$updated) {
				DB::rollBack();
				return response()->json(['message' => 'Trip already accepted by another driver'], 409);
			}

			$driver->available = false;
			$driver->save();

			DB::commit();

			return response()->json([
				'message' => 'Trip accepted successfully',
				'data' => new TripBookingResource($trip),
				'status' => true
			], 200);

		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error accepting trip: ' . $e->getMessage());
			return response()->json(['error' => 'Could not accept trip'], 500);
		}
	}

	public function startTrip(Request $request)
	{
		$user = auth()->user();

		$driver = Driver::where('user_id', $user->id)->first();
		if (!$driver || $user->user_type !== 'driver') {
			return response()->json([
				'message' => 'Only drivers can start trips'
			], 403);
		}
		$tripId = $request->input('trip_id');
		$trip = TripBooking::find($tripId);

		if (!$trip) {
			return response()->json([
				'message' => 'Trip not found'
			], 404);
		}

		if ($trip->trip_status !== 'accepted') {
			return response()->json([
				'message' => 'Trip cannot be started'
			], 400);
		}

		if ($trip->driver_id !== $driver->id) {
			return response()->json([
				'message' => 'You are not assigned to this trip'
			], 403);
		}

		$trip->update([
			'trip_status' => 'in_progress',
			'from_time' => now()
		]);

		return response()->json([
			'message' => 'Trip started successfully',
			'status' => true,
			'data' => [
				'trip_id' => $trip->id,
				'driver_id' => $driver->id,
				'trip_status' => $trip->trip_status,
				'from_time' => $trip->from_time
			]
		], 200);
	}

	public function completeTrip(Request $request)
	{
		$user = auth()->user();
		$driver = Driver::where('user_id', $user->id)->first();

		$tripId = $request->input('trip_id');

		$trip = TripBooking::find($tripId);

		if (!$trip) {
			return response()->json([
				'message' => 'Trip not found'
			], 404);
		}

		if ($trip->trip_status !== 'in_progress') {
			return response()->json([
				'message' => 'Trip cannot be completed'
			], 400);
		}

		$trip->update([
			'trip_status' => 'completed',
			'to_time' => now()
		]);

		$driver = $trip->driver;

		if (!$driver) {
			return response()->json([
				'message' => 'Driver not found'
			], 404);
		}

		$totalAmount = $trip->total_amount;
		$paymentMethod = $trip->payment;

		if ($paymentMethod === 'wallet') {
			$driverIncome = $totalAmount * 0.80;
			$driver->income += $driverIncome;

			WalletTransaction::create([
				'driver_id' => $driver->id,
				'amount' => $driverIncome,
				'type' => 'credit',
				'description' => 'Trip income (wallet payment)'
			]);
		} elseif ($paymentMethod === 'cash') {
			$platformFee = $totalAmount * 0.20;
			$driver->wallet_balance -= $platformFee;

			WalletTransaction::create([
				'driver_id' => $driver->id,
				'amount' => $platformFee,
				'type' => 'debit',
				'description' => 'Platform fee (cash payment)'
			]);
		}

		$driver->save();

		return response()->json([
			'status' => true,
			'message' => 'Trip completed successfully',
			'data' => [
				'trip_id' => $trip->id,
				'driver_id' => $driver->id,
				//'total_amount' => $totalAmount,
				//'driver_income' => $driverIncome,
				//'platform_fee' => $platformFee,
				//'new_wallet_balance' => $driver->wallet_balance,
				//'new_income' => $driver->income
			]
		], 200);
	}


	/*
	 *Show Driver Daily
	 */
	public function showDriverDailyIncome($driverId)
	{
		$today = now()->toDateString();

		$dailyIncome = TripBooking::where('driver_id', $driverId)
			->whereDate('to_date_time', $today)
			->where('status', 'completed')
			->sum(DB::raw('total_amount * 0.80'));

		return response()->json([
			'daily_income' => $dailyIncome
		], 200);
	}

	public function showDriverMonthlyIncome($driverId, $month, $year)
	{
		$monthlyIncome = TripBooking::where('driver_id', $driverId)
			->whereYear('to_date_time', $year)
			->whereMonth('to_date_time', $month)
			->where('status', 'completed')
			->sum(DB::raw('total_amount * 0.80'));

		return response()->json([
			'monthly_income' => $monthlyIncome
		], 200);
	}


}
