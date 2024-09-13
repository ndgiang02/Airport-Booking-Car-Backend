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
        'brand',
        'color',
        'seating_capacity',
        'license_plate',
        'initial_starting_price',
        'rate_per_km',
    ];

    protected $casts = [
        'driver_id' => 'integer',
    ];

}
