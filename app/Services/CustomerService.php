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

        $fcmToken = $customer->device_token;

        $notificationData = [
            'notification' => [
                'title' => 'Chuyến đi đã được xác nhận!',
                'body' => 'Tài xế sẽ sớm có mặt',
            ],
            'data' => [
                'trip_type' => 'accepted',
                'trip_id' => (string) $trip->id,
                'from_address' => $trip->from_address,
                'to_address' => $trip->to_address,
                'driver_name' => $trip->driver->user->name,
                'driver_phone' => $trip->driver->user->phone
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