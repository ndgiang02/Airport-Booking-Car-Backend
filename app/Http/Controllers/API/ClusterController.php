<?php

namespace App\Http\Controllers\API;

use App\Models\TripBooking;
use App\Models\Driver;
use App\Http\Controllers\Controller;
use App\Services\CustomerService;



class ClusterController extends Controller
{

    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function acceptCluster($clusterId, $driverId)
    {
       
        $tripsInCluster = TripBooking::where('cluster_group', $clusterId)->get();
    
        if ($tripsInCluster->isEmpty()) {
            return response()->json(['message' => 'No trips found in the cluster'], 404);
        }
    
        foreach ($tripsInCluster as $trip) {
            $trip->driver_id = $driverId;
            $trip->trip_status = 'accepted';
            $trip->save();
            $this->sendNotificationToPassenger($trip);
        }
    
        $driver = Driver::find($driverId);
        if ($driver) {
            $driver->available = false;
            $driver->save();
        }

        $this->customerService->sendNotificationTocustomer($trip);
    
        return response()->json(['message' => 'Cluster accepted by driver successfully.'], 200);
    }
}

