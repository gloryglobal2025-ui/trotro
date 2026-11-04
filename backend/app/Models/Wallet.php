<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'bonus_balance',
        'pending_balance',
        'last_transaction_at',
    ];

    protected $casts = [
        'balance' => 'float',
        'bonus_balance' => 'float',
        'pending_balance' => 'float',
        'last_transaction_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'user_id', 'user_id');
    }

    public function deposits()
    {
        return $this->hasMany(WalletDeposit::class, 'user_id', 'user_id');
    }

    public function bonuses()
    {
        return $this->belongsToMany(WalletBonus::class, 'user_wallet_bonus', 'user_id', 'wallet_bonus_id')->withPivot('amount', 'granted_at', 'is_used', 'used_at');
    }

    public function getAvailableBalanceAttribute(): float
    {
        return round((float) $this->balance + (float) $this->bonus_balance, 2);
    }
}
