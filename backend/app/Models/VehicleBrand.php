<?php

namespace App\Models;

use App\Support\Image;
use Illuminate\Database\Eloquent\Model;

class VehicleBrand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        return Image::url($this->logo);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
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
