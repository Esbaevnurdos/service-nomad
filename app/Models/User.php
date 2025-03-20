<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'role',
        'otp',
        'otp_expires_at',
    ];

    protected $hidden = [
        'otp',
    ];

    public function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
        ];
    }

    // Relationship with orders as a passenger
    public function passengerOrders()
    {
        return $this->hasMany(Order::class, 'passenger_id');
    }

    // Relationship with orders as a driver
    public function driverOrders()
    {
        return $this->hasMany(Order::class, 'driver_id');
    }
}
