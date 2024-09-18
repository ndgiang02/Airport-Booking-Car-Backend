<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Models\TripBooking;
use Illuminate\Http\Request;
use DB;
class DriverController extends Controller
{

	protected $limit;

	public function __construct()
	{
		$this->limit = 20;
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		$users = Driver::paginate($this->limit);
		return response()->json($users);
	}

	public function acceptTrip($tripId)
	{
		// Find the trip using the trip ID
		$trip = TripBooking::find($tripId);

		// Check if the trip exists
		if (!$trip) {
			return response()->json([
				'message' => 'Trip not found'
			], 404);
		}

		// Ensure the trip can be accepted only if it is in the 'requested' status
		if ($trip->status !== 'requested') {
			return response()->json([
				'message' => 'Trip cannot be accepted'
			], 400);
		}

		// Get the authenticated driver (Assuming authentication is already handled)
		$driver = auth()->user();

		// Check if the user is a driver
		if (!$driver || $driver->user_type !== 'driver') {
			return response()->json([
				'message' => 'Only drivers can accept trips'
			], 403);
		}


		$trip->update([
			'status' => 'accepted',
			'driver_id' => $driver->id,
		]);

		return response()->json([
			'message' => 'Trip accepted successfully',
			'data' => [
				'trip_id' => $trip->id,
				'driver_id' => $driver->id,
				'status' => $trip->status,
				'from_date_time' => $trip->from_date_time
			]
		], 200);
	}

	public function startTrip($tripId)
	{
		$trip = TripBooking::find($tripId);

		if (!$trip) {
			return response()->json([
				'message' => 'Trip not found'
			], 404);
		}

		if ($trip->status !== 'accepted') {
			return response()->json([
				'message' => 'Trip cannot be started'
			], 400);
		}

		$driver = auth()->user();

		if (!$driver || $driver->user_type !== 'driver') {
			return response()->json([
				'message' => 'Only drivers can start trips'
			], 403);
		}

		if ($trip->driver_id !== $driver->id) {
			return response()->json([
				'message' => 'You are not assigned to this trip'
			], 403);
		}

		$trip->update([
			'status' => 'in_progress',
			'from_date_time' => now()
		]);

		return response()->json([
			'message' => 'Trip started successfully',
			'data' => [
				'trip_id' => $trip->id,
				'driver_id' => $driver->id,
				'status' => $trip->status,
				'from_date_time' => $trip->from_date_time
			]
		], 200);
	}



	public function completeTrip($tripId)
	{
		$trip = TripBooking::find($tripId);

		if (!$trip) {
			return response()->json([
				'message' => 'Trip not found'
			], 404);
		}

		if ($trip->status !== 'in_progress') {
			return response()->json([
				'message' => 'Trip cannot be completed'
			], 400);
		}

		$trip->update([
			'status' => 'completed',
			'to_date_time' => now()
		]);

		$driver = $trip->driver;

		if (!$driver) {
			return response()->json([
				'message' => 'Driver not found'
			], 404);
		}

		$totalAmount = $trip->total_amount;
		$driverIncome = $totalAmount * 0.80;
		$platformFee = $totalAmount * 0.20;

		$driver->income += $driverIncome;

		$driver->wallet_balance -= $platformFee;

		$driver->save();

		return response()->json([
			'message' => 'Trip completed successfully and driver income/wallet updated',
			'data' => [
				'trip_id' => $trip->id,
				'driver_id' => $driver->id,
				'total_amount' => $totalAmount,
				'driver_income' => $driverIncome,
				'platform_fee' => $platformFee,
				'new_wallet_balance' => $driver->wallet_balance,
				'new_income' => $driver->income
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

	public function updateDriverLocation(Request $request)
	{
		$validatedData = $request->validate([
			'latitude' => 'required|numeric|between:-90,90',
			'longitude' => 'required|numeric|between:-180,180',
		]);

		$driver = auth()->user()->driver;

		if (!$driver) {
			return response()->json([
				'message' => 'Driver not found'
			], 404);
		}

		$driver->latitude = $validatedData['latitude'];
		$driver->longitude = $validatedData['longitude'];
		$driver->save();

		return response()->json([
			'message' => 'Driver location updated successfully',
			'data' => [
				'driver_id' => $driver->id,
				'latitude' => $driver->latitude,
				'longitude' => $driver->longitude,
			]
		], 200);
	}



}
