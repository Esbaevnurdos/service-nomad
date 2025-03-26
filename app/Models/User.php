<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use App\Models\Driver;
use App\Models\Passenger;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'role',
        'otp',
        'otp_expires_at',
        'latitude',
        'longitude',
        'is_available',

    ];

    protected $hidden = [
        'otp',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
    ];

    
    public function getAuthIdentifierName()
    {
        return 'phone'; // Authenticate using 'phone' instead of 'email'
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            if ($user->role === 'driver') {
                Driver::create([
                    'user_id' => $user->id,
                    'status' => 'offline', // Default status for drivers
                    'driver_phone' => $user->phone,
                ]);
            } elseif ($user->role === 'passenger') {
                Passenger::create([
                    'user_id' => $user->id,
                    'passenger_phone' => $user->phone,
                ]);
            }
        });
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function passenger()
    {
        return $this->hasOne(Passenger::class);
    }

    public function passengerOrders()
    {
        return $this->hasMany(Order::class, 'passenger_id');
    }

    public function driverOrders()
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

     public function driverDetails()
    {
        return $this->hasOne(Driver::class, 'user_id');
    }

    // Notifications sent to this user
    // public function notifications()
    // {
    //     return $this->hasMany(Notification::class);
    // }
}
