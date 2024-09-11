<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'user_id',
        'rating'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

