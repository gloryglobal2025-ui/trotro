<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    protected $fillable = [
        'route_id',
        'bus_stop_id',
        'sequence',
        'fare_from_origin',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'fare_from_origin' => 'decimal:2',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function stop()
    {
        return $this->belongsTo(BusStop::class, 'bus_stop_id');
    }
}
