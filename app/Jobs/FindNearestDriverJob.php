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
use Illuminate\Support\Facades\Cache;


class FindNearestDriverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

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
        $tripId = $this->trip->id;
        $attempts = Cache::get('trip_attempts_' . $tripId, 0);
        $maxAttempts = 3;
        if ($attempts >= $maxAttempts) {
            Log::info('Đã vượt quá số lần thử tìm tài xế cho trip ID: ' . $tripId);
            Cache::put('trip_exceeded_' . $tripId, true, now()->addMinutes(30));
            $trip = TripBooking::find($tripId);
            $trip->save();

            return;
        }

        if (Cache::get('trip_exceeded_' . $tripId, false)) {
            Log::info('Job đã vượt quá số lần thử, dừng lại cho trip ID: ' . $tripId);
            return;
        }

        $nearestDriver = DB::table('drivers')
            ->join('vehicles', 'drivers.id', '=', 'vehicles.driver_id')
            ->join('vehicle_types', 'vehicles.vehicle_type_id', '=', 'vehicle_types.id')
           ->select('drivers.id', 'drivers.user_id', 'drivers.latitude', 'drivers.longitude', DB::raw("(
                6371 * acos(
                    cos(radians($pickupLatitude)) 
                    * cos(radians(drivers.latitude)) 
                    * cos(radians(drivers.longitude) - radians($pickupLongitude)) 
                    + sin(radians($pickupLatitude)) 
                    * sin(radians(drivers.latitude))
                )
            ) AS distance"))
            ->where('drivers.available', 1)
            ->where('vehicle_types.id', $this->trip->vehicle_type)
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->first();

        if ($nearestDriver) {
            $trip = TripBooking::find($tripId);
            $this->sendPushNotificationToDriver($nearestDriver->id, $trip);
            $trip->save();

            Cache::put('trip_attempts_' . $tripId, $attempts + 1, now()->addMinutes(30));
            CheckDriverResponseJob::dispatch($tripId)->delay(now()->addSeconds(40));

        } else {
            Cache::put('trip_attempts_' . $tripId, $attempts + 1, now()->addMinutes(30));
            FindNearestDriverJob::dispatch($this->trip)->delay(now()->addSeconds(40));
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
                'type' => "trip_request",
                'trip_id' => (string) $trip->id,
                'from_address' => $trip->from_address,
                'to_address' => $trip->to_address,
                'scheduled_time' => $trip->scheduled_time->toDateTimeString(),
                'payment' => $trip->payment,
                'trip_type' => (string) $trip->trip_type,
                'total_amount' => (string) $trip->total_amount,
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
