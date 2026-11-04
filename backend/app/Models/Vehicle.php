<?php

namespace App\Models;

use App\Support\Image;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'driver_id',
        'brand_id',
        'make',
        'model',
        'year',
        'license_plate',
        'color',
        'capacity',
        'vehicle_type',
        'is_active',
        'vehicle_image',
        'request_status',
        'rejection_reason',
        'request_approved_at',
        'request_approved_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'request_approved_at' => 'datetime',
    ];

    protected $appends = [
        'vehicle_image_url',
    ];

    public function getVehicleImageUrlAttribute(): ?string
    {
        return Image::url($this->vehicle_image);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function brand()
    {
        return $this->belongsTo(VehicleBrand::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(VehicleAttribute::class, 'vehicle_attribute_vehicle');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'request_approved_by');
    }

    public function rides()
    {
        return $this->hasMany(Ride::class);
    }

    public function scopePending($query)
    {
        return $query->where('request_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('request_status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('request_status', 'rejected');
    }
}
