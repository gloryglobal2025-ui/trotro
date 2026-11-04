<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'origin_stop_id',
        'destination_stop_id',
        'route_name',
        'description',
        'estimated_duration',
    ];

    protected $casts = [
        'estimated_duration' => 'integer',
    ];

    public function origin()
    {
        return $this->belongsTo(BusStop::class, 'origin_stop_id');
    }

    public function destination()
    {
        return $this->belongsTo(BusStop::class, 'destination_stop_id');
    }

    public function fares()
    {
        return $this->hasMany(Fare::class);
    }

    public function stops()
    {
        return $this->hasMany(RouteStop::class)->orderBy('sequence');
    }

    public function fareBetween(int $fromStopId, int $toStopId): ?float
    {
        $from = $this->stops->firstWhere('bus_stop_id', $fromStopId);
        $to = $this->stops->firstWhere('bus_stop_id', $toStopId);

        if (! $from || ! $to || $from->sequence >= $to->sequence) {
            return null;
        }

        return round((float) $to->fare_from_origin - (float) $from->fare_from_origin, 2);
    }
}
