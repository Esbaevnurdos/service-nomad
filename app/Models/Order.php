<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'passenger_id',
        'driver_id',
        'passenger_phone',
        'pickup_lat',
        'pickup_lng',
        'dropoff_lat',
        'dropoff_lng',
        'distance_km',
        'duration_min',
        'fare',
        'status',
    ];

    // Relationship with the passenger (User)
    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    // Relationship with the driver (User) - allows null if no driver assigned
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id')->withDefault();
    }

    //     public function notifications()
    // {
    //     return $this->hasMany(Notification::class);
    // }
}
