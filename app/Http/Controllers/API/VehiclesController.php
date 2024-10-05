<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Http\Resources\VehicleResource;

class VehiclesController extends Controller
{
    public function getVehicleInfo()
    {
        $user = auth()->user();

        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => 'Driver not found',
            ], 404);
        }

        $vehicle = Vehicle::with('vehicleType')->where('driver_id', $driver->id)->first();

        if (!$vehicle) {
            return response()->json([
                'status' => false,
                'message' => 'No vehicle information found for this driver',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Vehicle information retrieved successfully',
            'data' => new VehicleResource($vehicle),
        ], 200);
    }

    public function getVehicleTypes()
    {
    
        $vehicleTypes = VehicleType::all(['id', 'name', 'seating_capacity', 'starting_price', 'rate_per_km', 'image']);

        $vehicleTypes->transform(function ($vehicleType) {
            $vehicleType->image_url = $vehicleType->image ? asset('storage/' . $vehicleType->image) : null;
            return $vehicleType;
        });

        return response()->json([
            'message'=>"Get vehicle succesfully",
            'status' => true,
            'data' => $vehicleTypes
        ], 200);
    }

}
