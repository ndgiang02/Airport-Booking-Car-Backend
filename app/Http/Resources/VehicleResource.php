<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'vehicle_type' => $this->vehicle_type,
            'brand' => $this->brand,
            'color' => $this->color,
            'license_plate' => $this->license_plate,
            'seating_capacity' => $this->seating_capacity,
            'initial_starting_price' => $this->initial_starting_price,
            'rate_per_km' => $this->rate_per_km,
        ];
    }
}
