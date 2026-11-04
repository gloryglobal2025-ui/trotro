<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletDeposit extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'method',
        'reference',
        'status',
        'notes',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
