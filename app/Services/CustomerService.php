<?php

namespace App\Services;

use App\Http\Controllers\API\NotificationController;
use Log;

class CustomerService
{
    public function sendNotificationTocustomer($trip)
    {
        $customer = $trip->customer;

        if (!$customer || !$customer->device_token) {
            Log::warning("No valid customer or FCM token for trip ID: " . $trip->id);
            return;
        }

        $trips = $trip->append(['driver_name', 'driver_mobile']);

        $fcmToken = $customer->device_token;

        if ($trip->driver && $trip->driver->user) {
            $driverName = $trip->driver->user->name;
            $driverPhone = $trip->driver->user->phone;
        } else {
            Log::warning("No driver information available for trip ID: " . $trip->id);
            $driverName = 'N/A'; 
            $driverPhone = 'N/A';
        }

        $notificationData = [
            'notification' => [
                'title' => 'Chuyến đi đã được xác nhận!',
                'body' => 'Tài xế sẽ sớm có mặt',
            ],
            'data' => [
                'type' => 'accepted',
                'driver_id' =>(string) $trip->driver_id,
                'trip_id' => (string) $trip->id,
                'from_address' => $trip->from_address,
                'to_address' => $trip->to_address,
                'driver_name' => $driverName,
                'driver_phone' => $driverPhone,
                'trip' => json_encode($trips),
            ],
        ];

        if ($fcmToken) {
            $notificationController = new NotificationController();
            $notificationController->sendNotification($fcmToken, $notificationData);
        } else {
            Log::error("No FCM token found for customer ID: " . $customer->id);
        }
    }

}