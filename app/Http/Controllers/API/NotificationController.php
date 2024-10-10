<?php

namespace App\Http\Controllers\API;

use Kreait\Firebase\Messaging\CloudMessage;
use App\Http\Controllers\Controller;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use Google\Client;

class NotificationController extends Controller
{
    protected $firebaseMessaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
        $this->firebaseMessaging = $factory->createMessaging();
    }

    public function sendNotification($fcmToken, $notificationData)
    {
        $serviceAccountPath = base_path(env('FIREBASE_CREDENTIALS'));

        if (!file_exists($serviceAccountPath)) {
            throw new \Exception("Firebase service account file not found at: {$serviceAccountPath}");
        }

        $client = new Client();
        $client->setAuthConfig($serviceAccountPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        $projectId = 'booking-app-backend-b0410';
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $fields = [
            'message' => [
                'token' => $fcmToken,
                'notification' => $notificationData['notification'],
                'data' => $notificationData['data'],
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $response = curl_exec($ch);
        Log::info('FCM Response: ' . $response);

        curl_close($ch);

        return $response;
    }

    public function sendNotificationToAllUsers(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $deviceTokens = Customer::whereNotNull('device_token')->pluck('device_token')->toArray();

        if (empty($deviceTokens)) {
            return response()->json([
                'status' => false,
                'message' => 'No device tokens found.'
            ], 400);
        }

        $notification = Notification::create($request->title, $request->body);

        $message = CloudMessage::new()->withNotification($notification)
            ->withData([
                'screen' => '/notifications',
            ]);

        try {
            $this->firebaseMessaging->sendMulticast($message, $deviceTokens);
            return response()->json([
                'status' => true,
                'message' => 'Notification sent to all users successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send notification. ' . $e->getMessage()
            ], 500);
        }
    }



}


