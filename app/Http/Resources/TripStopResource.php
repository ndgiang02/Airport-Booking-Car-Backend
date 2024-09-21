<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripStopResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'stop_address' => $this->address,
            'stop_lat' => $this->latitude,
            'stop_lng' => $this->longitude,
            'stop_order' => $this->stop_order,
        ];
    }
}
