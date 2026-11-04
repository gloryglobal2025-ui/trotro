<?php

namespace App\Models;

use App\Support\Image;
use Illuminate\Database\Eloquent\Model;

class BusStop extends Model
{
    protected $fillable = [
        'name',
        'location',
        'latitude',
        'longitude',
        'area',
        'zone_id',
        'image_path',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return Image::url($this->image_path);
    }

    public function routesAsOrigin()
    {
        return $this->hasMany(Route::class, 'origin_stop_id');
    }

    public function routesAsDestination()
    {
        return $this->hasMany(Route::class, 'destination_stop_id');
    }

    public function ridesAsOrigin()
    {
        return $this->hasMany(Ride::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
