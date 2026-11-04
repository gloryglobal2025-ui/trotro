<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLevel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'user_type',
        'commission_rate',
        'fare_discount',
        'daily_rides_limit',
        'min_rating',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'fare_discount' => 'decimal:2',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('user_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
