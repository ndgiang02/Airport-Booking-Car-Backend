<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\TripBooking;

class TripStop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['trip_booking_id', 'address', 'latitude', 'longitude', 'stop_order'];

    public function tripBooking()
    {
        return $this->belongsTo(TripBooking::class);
    }
}
