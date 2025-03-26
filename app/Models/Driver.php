<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'driver_phone', 'pickup_lat', 'pickup_lng', 'status', 'vehicle_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
