<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    protected $fillable = [
        'passenger_id',
        'driver_id',
        'vehicle_id',
        'pass_id',
        'route_id',
        'origin_stop_id',
        'destination_stop_id',
        'origin_latitude',
        'origin_longitude',
        'origin_address',
        'destination_latitude',
        'destination_longitude',
        'destination_address',
        'status',
        'requested_at',
        'accepted_at',
        'pickup_at',
        'dropoff_at',
        'completed_at',
        'cancelled_at',
        'driver_current_latitude',
        'driver_current_longitude',
        'driver_last_location_update',
        'distance_km',
        'duration_minutes',
        'fare_amount',
        'payment_status',
        'payment_method',
        'transaction_id',
        'payment_reference',
        'paid_at',
        'payment_metadata',
        'driver_rating',
        'driver_comment',
        'passenger_rating',
        'passenger_comment',
    ];

    protected $casts = [
        'origin_latitude' => 'float',
        'origin_longitude' => 'float',
        'destination_latitude' => 'float',
        'destination_longitude' => 'float',
        'distance_km' => 'float',
        'fare_amount' => 'float',
        'driver_rating' => 'float',
        'passenger_rating' => 'float',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'pickup_at' => 'datetime',
        'dropoff_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'driver_current_latitude' => 'float',
        'driver_current_longitude' => 'float',
        'driver_last_location_update' => 'datetime',
        'paid_at' => 'datetime',
        'payment_metadata' => 'array',
    ];

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function pass()
    {
        return $this->belongsTo(Pass::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function originStop()
    {
        return $this->belongsTo(BusStop::class, 'origin_stop_id');
    }

    public function destinationStop()
    {
        return $this->belongsTo(BusStop::class, 'destination_stop_id');
    }
}
