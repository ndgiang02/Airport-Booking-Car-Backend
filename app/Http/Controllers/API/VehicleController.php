<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
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
            'driver_id' => 'required|integer',
            'vehicle_type' => 'required|string',
            'initial_starting_price' => 'required|numeric',
            'rate_per_km' => 'required|numeric',
            'license_plate' => 'required|string|unique:vehicles',
            'seating_capacity' => 'required|integer',
        ]);

        $vehicle = Vehicle::create([
            'driver_id' => $request->driver_id,
            'vehicle_type' => $request->vehicle_type,
            'initial_starting_price' => $request->initial_starting_price,
            'rate_per_km' => $request->rate_per_km,
            'license_plate' => $request->license_plate,
            'seating_capacity' => $request->seating_capacity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle registered successfully',
            'vehicle' => $vehicle
        ], 201);
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
     * Xóa mềm phương tiện (Soft delete).
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
     * Khôi phục phương tiện đã bị xóa mềm.
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
     * Lấy danh sách các phương tiện đã bị xóa mềm.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function trashed()
    {
        $vehicles = Vehicle::onlyTrashed()->paginate($this->limit);

        return response()->json($vehicles);
    }
}
