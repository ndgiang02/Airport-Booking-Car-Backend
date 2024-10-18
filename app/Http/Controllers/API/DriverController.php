<?php

namespace App\Http\Controllers\API;

use App\Events\DriverLocationUpdated;
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
use App\Services\CustomerService;
use App\Jobs\AssignDriverToClusterJob;
use App\Jobs\FindNearestDriverJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use DB;

class DriverController extends Controller
{

	protected $customerService;

	public function __construct(CustomerService $customerService)
	{
		$this->customerService = $customerService;
	}


	public function registerDriver1(Request $request)
	{
		try {
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
			Log::info("Phát sự kiện DriverLocationUpdated cho tài xế ID: {$driver->id} với tọa độ ({$request->latitude}, {$request->longitude})");


			broadcast(new DriverLocationUpdated($driver->id, $request->latitude, $request->longitude));

			Log::info("Sự kiện DriverLocationUpdated đã được phát thành công.");

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

		if (!$driver) {
			return response()->json(['error' => 'Driver not found'], 404);
		}

		$tripId = $request->input('trip_id');

		$trip = TripBooking::with(['customer.user', 'stops'])->find($tripId);

		if (!$trip || !$trip->customer) {
			return response()->json(['error' => 'Trip or Customer not found'], 404);
		}

		if (!$trip) {
			return response()->json(['error' => 'Trip not found'], 404);
		}

		if ($trip->trip_status === 'accepted') {
			return response()->json(['message' => 'Trip already accepted by another driver'], 409);
		}

		try {
			DB::beginTransaction();

			/*
			$updated = TripBooking::where('id', $tripId)
				->where('trip_status', 'requested')
				->update(['trip_status' => 'accepted', 'driver_id' => $driver->id]);

			if (!$updated) {
				DB::rollBack();
				return response()->json(['message' => 'Trip already accepted by another driver'], 409);
			}
			*/

			$trip->driver_id = $driver->id;
			$trip->trip_status = 'accepted';
			$trip->save();
			$this->customerService->sendNotificationToCustomer($trip);

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

		$customer = $trip->customer;

		if (!$customer) {
			return response()->json([
				'message' => 'Customer not found'
			], 404);
		}

		$totalAmount = $trip->total_amount;
		$paymentMethod = $trip->payment;

		if ($paymentMethod === 'wallet') {
			// Trừ tiền từ ví khách hàng
			if ($customer->wallet_balance < $totalAmount) {
				return response()->json([
					'message' => 'Insufficient wallet balance'
				], 400);
			}

			$customer->wallet_balance -= $totalAmount;
			$customer->save();

			$driverIncome = $totalAmount * 0.80;
			$driver->income += $driverIncome;

			WalletTransaction::create([
				'customer_id' => $customer->id,
				'amount' => $totalAmount,
				'type' => 'debit',
				'description' => 'Trip payment (wallet)'
			]);

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
				'customer_id' => $customer->id,
				//'total_amount' => $totalAmount,
				//'driver_income' => $driverIncome,
				//'platform_fee' => $platformFee,
				//'new_wallet_balance' => $driver->wallet_balance,
				//'new_income' => $driver->income
			]
		], 200);
	}



	public function acceptCluster(Request $request)
	{

		$user = auth()->user();
		$driver = Driver::where('user_id', $user->id)->first();

		$clusterId = $request->input('cluster_id');

		$tripsInCluster = TripBooking::where('cluster_group', $clusterId)->get();


		if ($tripsInCluster->isEmpty()) {
			return response()->json(['message' => 'No trips found in the cluster'], 404);
		}

		try {
			$tripDetails = [];

			foreach ($tripsInCluster as $trip) {
				Log::info('Trip : ' . $trip);
				$trip->driver_id = $driver->id;
				$trip->trip_status = 'accepted';
				$trip->save();
				$this->customerService->sendNotificationToCustomer($trip);
				$tripDetails[] = [
					'total_amount' => $trip->total_amount,
					'from_address' => $trip->from_address,
					'to_address' => $trip->to_address,
					'from_lat' => $trip->from_lat,
					'from_lng' => $trip->from_lng,
					'to_lat' => $trip->to_lat,
					'to_lng' => $trip->to_lng,
					'scheduled_time' => $trip->scheduled_time->format('Y-m-d H:i'),
					'customer' => $trip->customer->id,
					'name' => $trip->customer->user->name,
					'mobile' => $trip->customer->user->mobile,
				];
			}

			$driver->available = false;
			$driver->save();

			return response()->json([
				'message' => 'Cluster accepted by driver successfully.',
				'status' => true,
				'data' => $tripDetails,
			], 200);

		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error accepting trip: ' . $e->getMessage());
			return response()->json(['error' => 'Could not accept trip'], 500);
		}

	}

	public function startCluster(Request $request)
	{
		$user = auth()->user();
		$driver = Driver::where('user_id', $user->id)->first();

		if (!$driver || $user->user_type !== 'driver') {
			return response()->json([
				'message' => 'Only drivers can start trips'
			], 403);
		}

		$clusterId = $request->input('cluster_id');

		$trips = TripBooking::where('cluster_group', $clusterId)->get();

		if ($trips->isEmpty()) {
			return response()->json([
				'message' => 'No trips found in the cluster'
			], 404);
		}

		foreach ($trips as $trip) {
			if ($trip->trip_status !== 'accepted') {
				return response()->json([
					'message' => 'All trips in the cluster must be accepted to start'
				], 400);
			}

			if ($trip->driver_id !== $driver->id) {
				return response()->json([
					'message' => 'You are not assigned to all trips in this cluster'
				], 403);
			}
		}

		foreach ($trips as $trip) {
			$trip->update([
				'trip_status' => 'in_progress',
				'from_time' => now()
			]);
		}

		return response()->json([
			'message' => 'All trips in the cluster started successfully',
			'status' => true,
			'data' => [
				'cluster_id' => $clusterId,
				'driver_id' => $driver->id,
				'trip_status' => 'in_progress',
				'from_time' => now()
			]
		], 200);


	}


	public function completeCluster(Request $request)
	{
		$user = auth()->user();
		$driver = Driver::where('user_id', $user->id)->first();

		if (!$driver || $user->user_type !== 'driver') {
			return response()->json([
				'message' => 'Only drivers can complete trips'
			], 403);
		}

		$clusterId = $request->input('cluster_id');
		$tripsInCluster = TripBooking::where('cluster_group', $clusterId)
			->where('trip_status', 'in_progress')
			->get();

		if ($tripsInCluster->isEmpty()) {
			return response()->json([
				'message' => 'No in-progress trips found in the cluster'
			], 404);
		}

		try {
			DB::beginTransaction();

			$totalAmount = 0;
			$driverIncome = 0;
			$platformFee = 0;

			foreach ($tripsInCluster as $trip) {
				$trip->update([
					'trip_status' => 'completed',
					'to_time' => now()
				]);

				$totalAmount += $trip->total_amount;
				$paymentMethod = $trip->payment;
				$customer = $trip->customer;

				if ($paymentMethod === 'wallet') {
					if ($customer && $customer->wallet_balance >= $trip->total_amount) {
						$customer->wallet_balance -= $trip->total_amount;
						$customer->save();

						WalletTransaction::create([
							'customer_id' => $customer->id,
							'amount' => $trip->total_amount,
							'type' => 'debit',
							'description' => 'Trip payment (wallet)'
						]);
					} else {
						DB::rollBack();
						return response()->json([
							'message' => 'Insufficient wallet balance for one or more trips'
						], 400);
					}

					$currentDriverIncome = $trip->total_amount * 0.80;
					$driverIncome += $currentDriverIncome;

					WalletTransaction::create([
						'driver_id' => $driver->id,
						'amount' => $currentDriverIncome,
						'type' => 'credit',
						'description' => 'Trip income (wallet payment)'
					]);
				} elseif ($paymentMethod === 'cash') {
					$currentPlatformFee = $trip->total_amount * 0.20;
					$platformFee += $currentPlatformFee;
					$driver->wallet_balance -= $currentPlatformFee;

					WalletTransaction::create([
						'driver_id' => $driver->id,
						'amount' => $currentPlatformFee,
						'type' => 'debit',
						'description' => 'Platform fee (cash payment)'
					]);
				}
			}

			$driver->available = true;
			$driver->income += $driverIncome;
			$driver->save();

			DB::commit();

			return response()->json([
				'status' => true,
				'message' => 'Cluster trips completed successfully',
				'data' => [
					'cluster_id' => $clusterId,
					'driver_id' => $driver->id,
					'total_amount' => $totalAmount,
					'driver_income' => $driverIncome,
					'platform_fee' => $platformFee,
					'new_wallet_balance' => $driver->wallet_balance,
					'new_income' => $driver->income
				]
			], 200);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error completing cluster: ' . $e->getMessage());
			return response()->json(['error' => 'Could not complete cluster'], 500);
		}
	}

	public function rejectClusterTrip(Request $request)
	{
		$user = auth()->user();
		$driver = Driver::where('user_id', $user->id)->first();

		$clusterId = $request->input('cluster_id');

		Log::info('Cluster Group Rejection: ' . $clusterId);

		$tripsInCluster = TripBooking::where('cluster_group', $clusterId)->get();

		if ($tripsInCluster->isEmpty()) {
			return response()->json(['message' => 'No trips found in the cluster'], 404);
		}

		try {
			DB::beginTransaction();

			foreach ($tripsInCluster as $trip) {
				if ($trip->trip_status == 'accepted') {
					return response()->json(['message' => 'Trip already accepted by another driver'], 400);
				}
			}

			$driver->available = true;
			$driver->save();

			AssignDriverToClusterJob::dispatch($clusterId, $tripsInCluster->first()->vehicle_type);
			DB::commit();

			return response()->json([
				'message' => 'Cluster rejected by driver, new driver assignment in progress.',
				'status' => true,
			], 200);

		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error rejecting trip: ' . $e->getMessage());
			return response()->json(['error' => 'Could not reject trip'], 500);
		}
	}

	public function rejectTrip(Request $request)
	{
		$user = auth()->user();
		$driver = Driver::where('user_id', $user->id)->first();
		$tripId = $request->input('trip_id');

		$trip = TripBooking::where('id', $tripId)->first();

		if (!$trip) {
			return response()->json(['message' => 'Trip not found'], 404);
		}

		if ($trip->trip_status == 'accepted') {
			return response()->json(['message' => 'Trip already accepted by another driver'], 400);
		}

		try {
			DB::beginTransaction();
			$driver->available = true;
			$driver->save();

			FindNearestDriverJob::dispatch($trip)->delay(now()->addMinutes(1));

			DB::commit();

			return response()->json([
				'message' => 'Trip rejected by driver, new driver assignment in progress.',
				'status' => true,
			], 200);

		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Error rejecting trip: ' . $e->getMessage());
			return response()->json(['error' => 'Could not reject trip'], 500);
		}
	}

	public function getTripsDriver(Request $request)
	{
		$user = auth()->user();
		$driver = Driver::where('user_id', $user->id)->first();

		if (!$driver) {
			return response()->json([
				'message' => 'Driver not found for this user',
				'data' => []
			], 404);
		}

		$driverId = $driver->id;

		$trips = TripBooking::with(['driver', 'customer'])
			->where('driver_id', $driverId)
			->whereIn('trip_status', ['completed'])
			->orderBy('to_time', 'desc')
			->get();

		if ($trips->isEmpty()) {
			return response()->json([
				'message' => 'No completed trips found for this driver',
				'data' => []
			], 404);
		}

		return response()->json([
			'message' => 'Fetch completed trips successfully.',
			'data' => $trips
		], 200);
	}




}
