<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'center_latitude',
        'center_longitude',
        'radius_km',
        'is_active',
    ];

    protected $casts = [
        'center_latitude' => 'decimal:8',
        'center_longitude' => 'decimal:8',
        'radius_km' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function busStops()
    {
        return $this->hasMany(BusStop::class);
    }

    public function fromZoneFares()
    {
        return $this->hasMany(ZoneFare::class, 'from_zone_id');
    }

    public function toZoneFares()
    {
        return $this->hasMany(ZoneFare::class, 'to_zone_id');
    }

    public function calculateDistanceTo($latitude, $longitude)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($latitude - $this->center_latitude);
        $dLon = deg2rad($longitude - $this->center_longitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
              cos(deg2rad($this->center_latitude)) * cos(deg2rad($latitude)) *
              sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function contains($latitude, $longitude)
    {
        return $this->calculateDistanceTo($latitude, $longitude) <= $this->radius_km;
    }
}
