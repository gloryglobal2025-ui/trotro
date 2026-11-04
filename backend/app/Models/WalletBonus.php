<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletBonus extends Model
{
    protected $fillable = [
        'name',
        'code',
        'amount',
        'type',
        'min_deposit',
        'min_wallet_balance',
        'usage_limit',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'min_deposit' => 'integer',
        'min_wallet_balance' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_wallet_bonus', 'wallet_bonus_id', 'user_id')->withPivot('amount', 'granted_at', 'is_used', 'used_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            });
    }

    public function scopeAvailable($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhere('used_count', '<', \DB::raw('usage_limit'));
            });
    }
}
