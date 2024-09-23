<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'driver_id',
        'vehicle_type_id',
        'brand',
        'model',
        'color',
        'license_plate',
    ];

    protected $casts = [
        'driver_id' => 'integer',
        'vehicle_type_id' => 'integer'
    ];

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

}
