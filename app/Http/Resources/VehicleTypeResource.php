<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VehicleTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'seating_capacity' => $this->seating_capacity,
            'starting_price' => $this->starting_price,
            'rate_per_km' => $this->rate_per_km,
            'image' => $this->image ? url('storage/' . $this->image) : null, 
        ];
    }
}
