<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fare extends Model
{
    protected $fillable = [
        'route_id',
        'amount',
        'fare_type',
        'description',
        'is_active',
        'effective_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_date' => 'date',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
