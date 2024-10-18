<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TripBooking;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\TripBookingResource;
use Illuminate\Support\Facades\Auth;
use App\Jobs\FindNearestDriverJob;
use App\Jobs\ClusteringJob;
use Carbon\Carbon;
use Validator;

class BookingController extends Controller
{

    public function tripBooking(Request $request)
    {
        $user = auth()->user();
		$customer = Customer::where('user_id', $user->id)->first();

        $validator = Validator::make($request->all(), [
            'from_address' => 'required|string|max:255',
            'from_lat' => 'required|numeric',
            'from_lng' => 'required|numeric',
            'to_address' => 'required|string|max:255',
            'to_lat' => 'required|numeric',
            'to_lng' => 'required|numeric',
            'scheduled_time' => 'required|date',
            'km' => 'required|numeric',
            'passenger_count' => 'required|integer',
            'total_amount' => 'required|numeric',
            'payment' => 'required|string',
            'round_trip' => 'boolean',
            'return_time' => 'nullable|date|after:scheduled_time',
            'trip_type' => 'required|string',
            'vehicle_type' => "required|integer",
            'stops' => 'nullable|array',
            'stops.*.stop_address' => 'nullable|string|max:255',
            'stops.*.stop_lat' => 'nullable|numeric',
            'stops.*.stop_lng' => 'nullable|numeric',
            'stops.*.stop_order' => 'nullable|integer',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tripBooking = TripBooking::create([
            'driver_id' => null,
            'customer_id' => $customer->id,
            'from_address' => $request->from_address,
            'from_lat' => $request->from_lat,
            'from_lng' => $request->from_lng,
            'to_address' => $request->to_address,
            'to_lat' => $request->to_lat,
            'to_lng' => $request->to_lng,
            'scheduled_time' => Carbon::parse($request->scheduled_time),
            'from_time' => null,
            'to_time' => null,
            'return_time' => $request->return_time ? Carbon::parse($request->return_time) : null,
            'vehicle_type' => $request->vehicle_type,
            'km' => $request->km,
            'passenger_count' => $request->passenger_count,
            'total_amount' => $request->total_amount,
            'payment' => $request->payment,
            'trip_type' => $request->trip_type,
            'trip_status' => 'requested',
            'round_trip' => $request->round_trip,
        ]);


        Log::info($tripBooking);

        if ($request->has('stops')) {
            foreach ($request->stops as $stop) {
                $tripBooking->stops()->create([
                    'trip_booking_id' => $tripBooking->id,
                    'address' => $stop['stop_address'],
                    'latitude' => $stop['stop_lat'],
                    'longitude' => $stop['stop_lng'],
                    'stop_order' => $stop['stop_order']
                ]);
            }
        }
    
        $scheduleTime = Carbon::parse($tripBooking->schedule_time);
        $currentTime = Carbon::now();

        if (is_null($tripBooking->schedule_time) || $scheduleTime->diffInMinutes($currentTime) <= 30) {
            FindNearestDriverJob::dispatch($tripBooking);
        } else {
            $delayTime = $scheduleTime->subMinutes(30);
            FindNearestDriverJob::dispatch($tripBooking)->delay($delayTime);
        }
        

       // FindNearestDriverJob::dispatch($tripBooking)->delay(now()->addMinutes(1));

        return response()->json([
            'status' => true,
            'message' => 'Trip booking created successfully without a driver.',
            'data' => $tripBooking,
        ], 201);
    }

    public function getTrips(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:upcoming,history',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = $request->input('status');
        $userId = $request->input('user_id');

        $query = TripBooking::with(['driver', 'customer'])
            ->whereHas('customer', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });

        if ($status === 'upcoming') {
            $query->whereIn('trip_status', ['requested', 'accepted']);
        } else {
            $query->withTrashed()->whereIn('trip_status', ['completed', 'canceled']);
        }

        $trips = $query->orderBy('scheduled_time', 'desc')->get()->append(['driver_name', 'driver_mobile']);

        if ($trips->isEmpty()) {
            return response()->json([
                'message' => 'No trips found for this customer',
                'data' => []
            ], 404);
        }

        return response()->json([
            'message' => 'Fetch trips successfully.',
            'data' => $trips
        ], 200);
    }


    public function cancelTrip(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:trip_bookings,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tripBooking = TripBooking::find($request->id);

        if (!$tripBooking) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $currentUser = Auth::user();
        $customer = $currentUser->customer;

        if ($tripBooking->customer_id !== $customer->id) {
            return response()->json(['message' => 'You can only cancel your own trips'], 403);
        }

        if ($tripBooking->trip_status !== 'requested') {
            return response()->json(['message' => 'Only requested trips can be canceled'], 400);
        }

        $tripBooking->update(['trip_status' => 'canceled']);
        $tripBooking->delete();

        return response()->json([
            'status' => true,
            'message' => 'Trip canceled successfully',
            'data' => new TripBookingResource($tripBooking)
        ], 200);
    }

    public function TripCluster(Request $request)
    {
        $user = auth()->user();
		$customer = Customer::where('user_id', $user->id)->first();

        $validator = Validator::make($request->all(), [
            'from_address' => 'required|string|max:255',
            'from_lat' => 'required|numeric',
            'from_lng' => 'required|numeric',
            'to_address' => 'required|string|max:255',
            'to_lat' => 'required|numeric',
            'to_lng' => 'required|numeric',
            'scheduled_time' => 'required|date',
            'km' => 'required|numeric',
            'vehicle_type' => "required|integer",
            'passenger_count' => 'required|integer',
            'trip_type' => 'required|string',
            'total_amount' => 'required|numeric',
            'payment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tripBooking = TripBooking::create([
            'driver_id' => null,
            'customer_id' => $customer->id,
            'from_address' => $request->from_address,
            'from_lat' => $request->from_lat,
            'from_lng' => $request->from_lng,
            'to_address' => $request->to_address,
            'to_lat' => $request->to_lat,
            'to_lng' => $request->to_lng,
            'scheduled_time' => Carbon::parse($request->scheduled_time),
            'from_time' => null,
            'to_time' => null,
            'return_time' => null,
            'km' => $request->km,
            'vehicle_type' => $request->vehicle_type,
            'passenger_count' => $request->passenger_count,
            'total_amount' => $request->total_amount,
            'payment' => $request->payment,
            'trip_status' => 'requested',
            'trip_type' => $request->trip_type,
            'round_trip' => false,
        ]);

    
        $pendingRequests = Cache::get('pending_requests_count', 0);
        Log::info('Current pending_requests_count from cache: ' . $pendingRequests);  

        $pendingRequests++;
        Cache::put('pending_requests_count', $pendingRequests, 60);

        Log::info('Updated pending_requests_count after increment: ' . $pendingRequests);


        if ($pendingRequests >= 2) {
            try {
                ClusteringJob::dispatch();
            } catch (\Exception $e) {
                Log::error('Error dispatching ClusteringJob: ' . $e->getMessage());
            }
            Cache::put('pending_requests_count', 0, 300);
        }


        return response()->json([
            'status' => true,
            'message' => 'Trip booking created successfully without a driver.',
            'data' => $tripBooking,
        ], 201);
    }

}
