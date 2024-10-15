<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'user_id', 
        'license_no', 
        'rating', 
        'available',
        'latitude',
        'longitude',
        'income',
        'wallet_balance',
        'device_token'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class, 'driver_id');
    }
}
