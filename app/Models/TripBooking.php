<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripBooking extends Model
{
    protected $fillable = [
        'driver_id', 
        'customer_id', 
        'from_address',
        'from_lat',
        'from_lng',
        'to_address',
        'to_lat',
        'to_lng',
        'from_date_time', 
        'to_date_time',
        'km', 
        'total_amount', 
        'payment',
        'status',
    ];

    protected $casts = [
        'from_location' => 'array',
        'to_location' => 'array',
        'from_date_time' => 'datetime',
        'to_date_time' => 'datetime',
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
}
