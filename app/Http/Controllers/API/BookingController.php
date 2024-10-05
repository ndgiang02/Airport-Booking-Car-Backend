<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TripBooking;
use App\Models\Driver;
use App\Http\Resources\TripBookingResource;
use App\Http\Resources\DriverResource;
use App\Jobs\FindNearestDriverJob;
use Carbon\Carbon;
use Validator;

class BookingController extends Controller
{

    public function tripBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'from_address' => 'required|string|max:255',
            'from_lat' => 'required|numeric',
            'from_lng' => 'required|numeric',
            'to_address' => 'required|string|max:255',
            'to_lat' => 'required|numeric',
            'to_lng' => 'required|numeric',
            'scheduled_time' => 'required|date',
            'km' => 'required|numeric',
            'total_amount' => 'required|numeric',
            'payment' => 'required|string',
            'round_trip' => 'boolean',
            'return_time' => 'nullable|date|after:scheduled_time',
            'vehicle_type' => "required|string",
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
            'customer_id' => $request->customer_id,
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
            'total_amount' => $request->total_amount,
            'payment' => $request->payment,
            'trip_status' => 'requested',
            'round_trip' => $request->round_trip,
        ]);

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



        FindNearestDriverJob::dispatch($tripBooking)->delay(now()->addMinutes(1));

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
            $query->whereIn('trip_status', ['completed', 'cancelled']);
        }

        $trips = $query->orderBy('scheduled_time', 'desc')->get();

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


    public function cancelBooking(Request $request, $id)
    {
        $tripBooking = TripBooking::find($id);

        if (!$tripBooking) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $currentUser = $request->user();

        if ($tripBooking->user_id !== $currentUser->id) {
            return response()->json(['message' => 'You can only cancel your own trips'], 403);
        }

        if ($tripBooking->status !== 'requested') {
            return response()->json(['message' => 'Only requested trips can be canceled'], 400);
        }

        $tripBooking->update(['trip_status' => 'canceled']);

        return response()->json([
            'message' => 'Trip canceled successfully',
            'data' => new TripBookingResource($tripBooking)
        ], 200);
    }


}
