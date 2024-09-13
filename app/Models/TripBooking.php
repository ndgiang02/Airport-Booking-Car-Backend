<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripBooking extends Model
{

    use SoftDeletes;
    protected $fillable = [
        'driver_id', 
        'customer_id', 
        'from_address',
        'from_lat',
        'from_lng',
        'to_address',
        'to_lat',
        'to_lng',
        'scheduled_time',
        'from_time',    
        'to_time',       
        'return_time',
        'round_trip',
        'km', 
        'total_amount', 
        'payment',
        'trip_status',
    ];

    protected $casts = [
        'from_location' => 'array',
        'to_location' => 'array',
        'scheduled_time' => 'datetime',
        'from_time' => 'datetime',
        'to_time' => 'datetime',
        'return_time' => 'datetime',
        'fare' => 'decimal:2',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function stops()
    {
        return $this->hasMany(TripStop::class, 'trip_booking_id');
    }
}
