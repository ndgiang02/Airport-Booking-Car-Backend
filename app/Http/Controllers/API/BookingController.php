<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TripBooking;
use App\Models\Driver;
use App\Http\Resources\TripBookingResource;
use App\Http\Resources\DriverResource;
use Carbon\Carbon;
use Validator;

class BookingController extends Controller
{
    public function createBooking(Request $request)
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
        'stops' => 'nullable|array',
        'stops.*.stop_address' => 'required|string|max:255',
        'stops.*.stop_lat' => 'required|numeric',
        'stops.*.stop_lng' => 'required|numeric',
        'stops.*.stop_order' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Logic tìm tài xế gần nhất dựa trên tọa độ
    $driver = Driver::select('*')
        ->selectRaw(
            '(6371 * acos(cos(radians(?)) 
            * cos(radians(latitude)) 
            * cos(radians(longitude) - radians(?)) 
            + sin(radians(?)) 
            * sin(radians(latitude)))) AS distance',
            [$request->from_lat, $request->from_lng, $request->from_lat]
        )
        ->where('available', true)
        ->having('distance', '<=', 10) // Giới hạn tìm tài xế trong bán kính 10 km
        ->orderBy('distance') // Sắp xếp theo khoảng cách từ gần đến xa
        ->first(); // Lấy tài xế gần nhất

    if (!$driver) {
        return response()->json(['message' => 'No available drivers found within 10 km.'], 404);
    }

    // Tạo chuyến đi mới
    $tripBooking = TripBooking::create([
        'driver_id' => $driver->id, // Gán tài xế vừa tìm được
        'customer_id' => $request->customer_id,
        'from_address' => $request->from_address,
        'from_lat' => $request->from_lat,
        'from_lng' => $request->from_lng,
        'to_address' => $request->to_address,
        'to_lat' => $request->to_lat,
        'to_lng' => $request->to_lng,
        'scheduled_time' => Carbon::parse($request->scheduled_time),
        'from_time' => Carbon::parse($request->scheduled_time), // Nếu cần thêm xử lý cho `from_time`
        'to_time' => Carbon::parse($request->to_time),
        'return_time' => $request->return_time ? Carbon::parse($request->return_time) : null,
        'km' => $request->km,
        'total_amount' => $request->total_amount,
        'payment' => $request->payment,
        'status' => 'requested',
        'round_trip' => $request->round_trip,
    ]);

    if ($request->has('stops')) {
        foreach ($request->stops as $stop) {
            if (isset($stop['address']) && isset($stop['stop_lat']) && isset($stop['stop_lng'])) {
                $tripBooking->stops()->create([
                    'trip_booking_id' => $tripBooking->id,
                    'address' => $stop['address'],
                    'latitude' => $stop['latitude'],
                    'longitude' => $stop['longitude'],
                    'stop_order' => $stop['stop_order']
                ]);
            } else {
                return response()->json(['error' => 'Missing required stop data'], 422);
            }
        }
    }

    return response()->json([
        'message' => 'Trip booking created successfully with a driver assigned.',
        'data' => new TripBookingResource($tripBooking),
        'driver' => new DriverResource($driver) // Trả về thông tin tài xế
    ], 201);
}


    public function getBooking($id)
    {
        $tripBooking = TripBooking::find($id);

        if (!$tripBooking) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        return response()->json([
            'data' => new TripBookingResource($tripBooking)
        ], 200);
    }

    public function cancelBooking(Request $request, $id)
    {
        $tripBooking = TripBooking::find($id);

        if (!$tripBooking) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        if ($tripBooking->status !== 'requested') {
            return response()->json(['message' => 'Only requested trips can be canceled'], 400);
        }

        $tripBooking->update(['status' => 'canceled']);

        return response()->json([
            'message' => 'Trip canceled successfully',
            'data' => new TripBookingResource($tripBooking)
        ], 200);
    }


    /*
     *Find Driver
     */
    public function findDriverForImmediateTrip($trip)
    {
        $fromLat = $trip->from_lat;
        $fromLng = $trip->from_lng;

        $drivers = Driver::select('*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) 
            * cos(radians(latitude)) 
            * cos(radians(longitude) - radians(?)) 
            + sin(radians(?)) 
            * sin(radians(latitude)))) AS distance',
                [$fromLat, $fromLng, $fromLat]
            )
            ->where('available', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', 3)
            ->orderBy('distance')
            ->first();

        return $drivers;
    }


    public function findDriverForScheduledTrip($trip)
    {
        $scheduledTime = $trip->scheduled_time;
        $fromLat = $trip->from_lat;
        $fromLng = $trip->from_lng;
    
        $drivers = Driver::select('*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians(?)) 
                + sin(radians(?)) 
                * sin(radians(latitude)))) AS distance',
                [$fromLat, $fromLng, $fromLat]
            )
            ->where('available', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereDoesntHave('trips', function ($query) use ($scheduledTime) {
                $query->where(function ($q) use ($scheduledTime) {
                    $q->where('from_time', '<=', $scheduledTime)
                      ->where('to_time', '>=', $scheduledTime);
                });
            })
            ->having('distance', '<=', 3)
            ->orderBy('distance') 
            ->first(); 
    
        return $drivers;
    }

}
