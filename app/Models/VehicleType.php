<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'type', 
        'name', 
        'starting_price', 
        'rate_per_km', 
        'seating_capacity',
        'image',
        
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
