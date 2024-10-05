<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'amount',
        'type',
        'description'
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}