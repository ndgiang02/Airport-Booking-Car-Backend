<?php

namespace App\Jobs;

use App\Models\TripBooking;
use App\Models\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use NotificationController;

class RunClusteringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $trips = TripBooking::where('trip_status', 'requested')
            ->where('trip_type', 'airport_sharing')
            ->get();

        $locations = $trips->map(function ($trip) {
            return [
                'from_lat' => $trip->from_lat,
                'from_lng' => $trip->from_lng,
                'to_lat' => $trip->to_lat,
                'to_lng' => $trip->to_lng,
                'scheduled_time' => $trip->scheduled_time->format('Y-m-d H:i'),
                'passenger_count' => $trip->passenger_count,
                'vehicle_type' => $trip->vehicle_type,
                'seating_capacity' => $trip->vehicleType->seating_capacity
            ];
        })->toArray();

        $client = new \GuzzleHttp\Client();
        $response = $client->post('http://python-api:5000/cluster', [
            'json' => ['trips' => $locations]
        ]);

        $result = json_decode($response->getBody(), true);

        Log::info('Kết quả clusters:', $result['clusters']);
        Log::info('Kết quả vehicle_types:', $result['vehicle_types']);

        if ($result['status'] == 'success') {
            foreach ($trips as $index => $trip) {
                $trip->cluster_group = $result['clusters'][$index];
                $trip->save();
            }



            $clusters = collect($result['clusters'])->unique();

            foreach ($clusters as $clusterId) {
                $desiredVehicleType = $result['vehicle_types'][$clusterId];
                $this->assignDriverToCluster($clusterId, $desiredVehicleType);
            }
        }
    }


    private function assignDriverToCluster($clusterId, $desiredVehicleType)
    {
        Log::info("Search Driver: " . $clusterId);

        $drivers = Driver::where('available', true)
            ->whereHas('vehicles', function ($query) use ($desiredVehicleType) {
                $query->where('vehicle_type_id', $desiredVehicleType);
            })
            ->get(['drivers.*']);

        $selectedDriver = null;
        $shortestDistance = PHP_INT_MAX;

        foreach ($drivers as $driver) {
            $firstTrip = TripBooking::where('cluster_group', $clusterId)->first();
            if ($firstTrip) {
                $distance = $this->calculateDistance(
                    $driver->latitude,
                    $driver->longitude,
                    $firstTrip->from_lat,
                    $firstTrip->from_lng
                );

                if ($distance < $shortestDistance) {
                    $shortestDistance = $distance;
                    $selectedDriver = $driver;
                }
            }
        }

        if ($selectedDriver) {
            $this->assignTripsToDriver($selectedDriver, $clusterId);
        } else {
            Log::warning("Not found Driver: " . $clusterId);
        }
    }

    private function assignTripsToDriver($driver, $trips)
    {
        $tripDetails = $trips->map(function ($trip) {
            return [
                'trip_id' => $trip->id,
                'from_address' => $trip->from_address,
            ];
        })->toArray();

        foreach ($trips as $trip) {
            $trip->driver_id = $driver->id;
            $trip->trip_status = 'accepted';
            $trip->save();
        }

        $this->sendPushNotificationToDriver($driver->id, $tripDetails);

        $driver->available = false;
        $driver->save();
    }

    private function sendPushNotificationToDriver($driverId, $tripDetails)
    {
        $driver = Driver::find($driverId);

        if (!$driver) {
            Log::error("Tài xế không tìm thấy với ID: " . $driverId);
            return;
        }

        $fcmToken = $driver->device_token;

        $tripDetailsString = collect($tripDetails)->map(function ($detail) {
            return 'Trip ID: ' . $detail['trip_id'] . ', Pickup: ' . $detail['from_address'];
        })->implode('; ');

        $notificationData = [
            'notification' => [
                'title' => 'Yêu cầu chuyến mới',
                'body' => 'Bạn có yêu cầu chuyến mới. Các chuyến: ' . $tripDetailsString,
            ],
            'data' => [
                'type' => "trip_request",
                'trip_details' => $tripDetails,
            ],
        ];

        if ($fcmToken) {
            $notificationController = new NotificationController();
            $notificationController->sendNotification($fcmToken, $notificationData);
        } else {
            Log::error("FCM Token Not Found ID: " . $driverId);
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
