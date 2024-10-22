<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driverId;
    public $latitude;
    public $longitude;

    public function __construct($driverId, $latitude, $longitude)
    {
        $this->driverId = $driverId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;

        Log::info('Broadcasting DriverLocationUpdated event', [
            'driverId' => $this->driverId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('driver-location.' . $this->driverId);
    }

    public function broadcastAs()
    {
        return 'DriverLocationUpdated';
    }

    public function broadcastWith()
    {
        return [
            'driverId' => $this->driverId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

}
