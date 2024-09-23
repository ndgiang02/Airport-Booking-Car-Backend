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
            'driver_id' => $this->driver_id,
            'vehicle_type_id' => $this->vehicle_type_id,
            'brand' => $this->brand,
            'model' => $this->model,
            'color' => $this->color,
            'license_plate' => $this->license_plate,
            'vehicle_type' => new VehicleTypeResource($this->vehicleType),
        ];
    }
}
