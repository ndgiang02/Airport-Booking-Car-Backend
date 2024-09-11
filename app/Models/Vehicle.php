<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_type',
        'rate_per_km',
        'license_plate',
        'seating_capacity',
        'initial_starting_price',
        'image'
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
