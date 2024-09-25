<?php

namespace App\Jobs;

use App\Models\Driver;
use App\Models\TripBooking;
use App\Http\Controllers\API\NotificationController;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FindNearestDriverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $trip;

    public function __construct(TripBooking $trip)
    {
        $this->trip = $trip;
    }

    public function handle()
    {

        Log::info('FindNearestDriverJob started for trip ID: ' . $this->trip->id);

        $pickupLatitude = $this->trip->from_lat;
        $pickupLongitude = $this->trip->from_lng;
        $radius = 10;

        $nearestDriver = DB::table('drivers')
            ->select('id', 'user_id', 'latitude', 'longitude', 'device_token', DB::raw("(
                6371 * acos(
                    cos(radians($pickupLatitude)) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians($pickupLongitude)) 
                    + sin(radians($pickupLatitude)) 
                    * sin(radians(latitude))
                )
            ) AS distance"))
            ->where('available', 1)
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->first();

        if ($nearestDriver) {
            $trip = TripBooking::find($this->trip->id);
            $trip->driver_id = $nearestDriver->id;
            $trip->trip_status = 'accepted';
            $trip->save();

            $this->sendPushNotificationToDriver($nearestDriver->id, $trip);
        } else {
            //Chua biet làm gi
        }
    }

    private function sendPushNotificationToDriver($driverId, $trip)
    {
        $driver = Driver::find($driverId);

        if (!$driver) {
            Log::error("Driver not found with ID: " . $driverId);
            return;
        }

        $fcmToken = $driver->device_token;
        $notificationData = [
            'notification' => [
                'title' => 'New Trip Request',
                'body' => 'You have a new trip request. Tap to view details.',
            ],
            'data' => [
                'trip_id' => (string) $trip->id,
                'pickup_location' => $trip->from_address,
                'destination' => $trip->to_address,
                'scheduled_time' => $trip->scheduled_time->toDateTimeString(),
                'trip_type' => (string) $trip->trip_type,
                'total_amount' => (string) $trip->total_amount,
                'trip_status' => $trip->trip_status,
            ],
        ];
        if ($fcmToken) {
            $notificationController = new NotificationController();
            $notificationController->sendNotification($fcmToken, $notificationData);
        } else {
            Log::error("FCM Token not found for driver ID: " . $driverId);
        }
    }



}