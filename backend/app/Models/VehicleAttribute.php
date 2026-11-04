<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleAttribute extends Model
{
    protected $fillable = [
        'type',
        'name',
        'value',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_attribute_vehicle');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
