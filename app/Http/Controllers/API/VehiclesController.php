<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehiclesController extends Controller
{
    protected $limit;

    public function __construct()
    {
        $this->limit = 20;
    }

    /**
     * Lấy danh sách các phương tiện, phân trang.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $vehicles = Vehicle::paginate($this->limit);
        return response()->json($vehicles);
    }

    /**
     * Lưu phương tiện mới vào cơ sở dữ liệu.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerVehicle(Request $request)
    {
        $request->validate([
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'brand' => 'required|string',
            'color' => 'required|string',
            'seating_capacity' => 'required|integer',
            'license_plate' => 'required|string|unique:vehicles',
        ]);

        $vehicle = Vehicle::create([
            'driver_id' => auth()->id(),
            'vehicle_type_id' => $request->input('vehicle_type_id'),
            'brand' => $request->input('brand'),
            'color' => $request->input('color'),
            'seating_capacity' => $request->input('seating_capacity'),
            'license_plate' => $request->input('license_plate'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle register successful.',
            'data' => $vehicle
        ]);
    }

    /**
     * Lay cau hinh phuong tien
     * 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Lấy thông tin chi tiết của phương tiện.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }

        return response()->json($vehicle);
    }

    /**
     * Cập nhật thông tin của phương tiện.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_type' => 'sometimes|required|string|max:255',
            'initial_starting_price' => 'sometimes|required|numeric',
            'rate_per_km' => 'sometimes|required|numeric',
            'license_plate' => 'sometimes|required|string|max:255|unique:vehicles,license_plate,' . $id,
            'seating_capacity' => 'sometimes|required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle
        ]);
    }

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully'
        ]);
    }

    /**
     * 
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        $vehicle = Vehicle::withTrashed()->find($id);

        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }

        $vehicle->restore();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle restored successfully'
        ]);
    }

    /**
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function trashed()
    {
        $vehicles = Vehicle::onlyTrashed()->paginate($this->limit);

        return response()->json($vehicles);
    }
}
