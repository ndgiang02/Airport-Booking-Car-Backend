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
        'vehicle_type',
        'initial_starting_price',
        'rate_per_km',
        'license_plate',
        'seating_capacity',
        'initial_starting_price',
    ];

    protected $casts = [
        'driver_id' => 'integer',
    ];

}
