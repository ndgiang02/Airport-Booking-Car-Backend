<?php

namespace App\Jobs;

use App\Models\TripBooking;
use App\Models\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\API\NotificationController;
use DB;
use Log;

class AssignDriverToClusterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $clusterId;
    protected $desiredVehicleType;

    public function __construct($clusterId, $desiredVehicleType)
    {
        $this->clusterId = $clusterId;
        $this->desiredVehicleType = $desiredVehicleType;
    }

    public function handle()
    {

        $tripsInCluster = TripBooking::where('cluster_group', $this->clusterId)->get();

        if ($tripsInCluster->isEmpty()) {
            Log::warning("No trips found in cluster: " . $this->clusterId);
            return;
        }

        $averageLat = $tripsInCluster->avg('from_lat');
        $averageLng = $tripsInCluster->avg('from_lng');
        $radius = 10;

        $nearestDriver = DB::table('drivers')
            ->join('vehicles', 'drivers.id', '=', 'vehicles.driver_id')
            ->join('vehicle_types', 'vehicles.vehicle_type_id', '=', 'vehicle_types.id')
            ->select('drivers.id', 'drivers.user_id', 'drivers.latitude', 'drivers.longitude', DB::raw("(
                6371 * acos(
                    cos(radians($averageLat)) 
                    * cos(radians(drivers.latitude)) 
                    * cos(radians(drivers.longitude) - radians($averageLng)) 
                    + sin(radians($averageLat)) 
                    * sin(radians(drivers.latitude))
                )
            ) AS distance"))
            ->where('drivers.available', 1)
            ->where('vehicle_types.id', $this->desiredVehicleType)
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->first();

            static $notifiedDrivers = [];

            if ($nearestDriver) {
                if (!in_array($nearestDriver->id, $notifiedDrivers)) {
                    $this->sendPushNotificationToDriver($nearestDriver->id, $tripsInCluster);
                    $notifiedDrivers[] = $nearestDriver->id;
                }
            } else {
                Log::info('Không tìm thấy tài xế trong bán kính cho cluster: ' . $this->clusterId);
                $this->handleTripsWithoutDriver($this->clusterId);
            }
    }


    private function sendPushNotificationToDriver($driverId, $tripDetails)
    {
        $driver = Driver::find($driverId);

        if (!$driver) {
            Log::error("Tài xế không tìm thấy với ID: " . $driverId);
            return;
        }

        $fcmToken = $driver->device_token;

        $notificationData = [
            'notification' => [
                'title' => 'Yêu cầu chuyến mới',
                'body' => 'Bạn có yêu cầu chuyến mới.',
            ],
            'data' => [
                'type' => "trip_cluster_request",
                'trip_type' => $tripDetails->first()->trip_type,
                'cluster_id' => (string)$this->clusterId,
                'from_address' => $tripDetails->first()->from_address,
                'to_address' => $tripDetails->first()->to_address,
            ],
        ];

        if ($fcmToken) {
            $notificationController = new NotificationController();
            $notificationController->sendNotification($fcmToken, $notificationData);
        } else {
            Log::error("FCM Token Not Found ID: " . $driverId);
        }
    }

    private function handleTripsWithoutDriver($clusterId)
    {
        $tripsWithoutDriver = TripBooking::where('cluster_group', $clusterId)->get();
        foreach ($tripsWithoutDriver as $trip) {
            $trip->trip_status = 'requested';
            $trip->save();
        }

        Log::info("No drivers available for trips in cluster: " . $clusterId);
    }
}
