<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripStopResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'stop_address' => $this->stop_address,
            'stop_lat' => $this->stop_lat,
            'stop_lng' => $this->stop_lng,
            'stop_order' => $this->stop_order,
        ];
    }
}
