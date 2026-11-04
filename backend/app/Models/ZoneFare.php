<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneFare extends Model
{
    protected $fillable = [
        'from_zone_id',
        'to_zone_id',
        'base_fare',
        'rate_per_km',
        'min_fare',
        'is_active',
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'rate_per_km' => 'decimal:2',
        'min_fare' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function fromZone()
    {
        return $this->belongsTo(Zone::class, 'from_zone_id');
    }

    public function toZone()
    {
        return $this->belongsTo(Zone::class, 'to_zone_id');
    }

    public function calculateFare($distanceKm)
    {
        $calculatedFare = $this->base_fare + ($distanceKm * $this->rate_per_km);

        return max($calculatedFare, $this->min_fare);
    }
}
