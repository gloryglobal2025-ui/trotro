<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassChargeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_USSD = 'ussd';

    public const TTL_SECONDS = 180;

    protected $fillable = [
        'pass_id',
        'driver_id',
        'route_id',
        'fare_id',
        'ride_id',
        'amount',
        'customer_phone',
        'destination',
        'status',
        'channel',
        'expires_at',
        'approved_at',
        'declined_at',
        'cancelled_at',
        'completed_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'float',
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function pass()
    {
        return $this->belongsTo(Pass::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function fare()
    {
        return $this->belongsTo(Fare::class);
    }

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function hasExpired(): bool
    {
        return $this->isPending() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function secondsLeft(): int
    {
        if (! $this->isPending() || $this->expires_at === null) {
            return 0;
        }

        return max(0, $this->expires_at->getTimestamp() - now()->getTimestamp());
    }

    public function markExpired(): bool
    {
        if (! $this->hasExpired()) {
            return false;
        }

        $updated = static::whereKey($this->getKey())
            ->where('status', self::STATUS_PENDING)
            ->where('expires_at', '<=', now())
            ->update([
                'status' => self::STATUS_EXPIRED,
                'failure_reason' => 'The customer did not answer in time.',
                'updated_at' => now(),
            ]);

        $this->refresh();

        return $updated > 0;
    }
}
