<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\TripStopResource;

class TripBookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'customer' => $this->customer,
            'from_address' => $this->from_address,
            'from_lat' => $this->from_lat,
            'from_lng' => $this->from_lng,
            'to_address' => $this->to_address,
            'to_lat' => $this->to_lat,
            'to_lng' => $this->to_lng,
            'from_date_time' => $this->from_date_time,
            'to_date_time' => $this->to_date_time,
            'return_date_time' => $this->return_date_time,
            'km' => $this->km,
            'total_amount' => $this->total_amount,
            'payment' => $this->payment,
            'status' => $this->status,
            'is_round_trip' => $this->is_round_trip,
            'stops' => TripStopResource::collection($this->stops),
            'driver' => $this->driver ? [
                'id' => $this->driver->id,
                'name' => $this->driver->name,
                'vehicle' => $this->driver->vehicle ? $this->driver->vehicle->toArray() : null, 
            ] : null,
        ];
    }
}
