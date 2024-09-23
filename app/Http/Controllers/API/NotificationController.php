<?php

namespace App\Http\Controllers\API;

use Kreait\Firebase\Messaging\CloudMessage;
use App\Http\Controllers\Controller;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Factory;
use Illuminate\Http\Request;
use App\Models\Customer;

class NotificationController extends Controller
{
    protected $firebaseMessaging;

    public function __construct()
    {
        $factory = (new Factory) ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
        $this->firebaseMessaging = $factory->createMessaging();
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


