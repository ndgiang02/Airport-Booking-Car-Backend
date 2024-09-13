<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
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
            'id' => $this->id,
            'user_id' => $this->user_id,
            'license_no' => $this->license_no,
            'rating' => $this->rating,
            'available' => $this->available,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')), 
            'token'=>$this->token,
        ];
    }
}

