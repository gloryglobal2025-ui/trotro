<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Pass extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const APPROVAL_DEVICE_PIN = 'device_pin';

    public const APPROVAL_CUSTOMER_PHONE = 'customer_phone';

    public const PIN_MAX_ATTEMPTS = 3;

    public const PIN_LOCK_MINUTES = 10;

    protected $fillable = [
        'user_id',
        'code',
        'status',
        'approval_channel',
        'issued_by',
        'notes',
        'last_used_at',
        'ride_count',
        'pin_hash',
        'pin_set_at',
        'pin_attempts',
        'pin_locked_until',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'ride_count' => 'integer',
        'pin_set_at' => 'datetime',
        'pin_attempts' => 'integer',
        'pin_locked_until' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function rides()
    {
        return $this->hasMany(Ride::class);
    }

    public function chargeRequests()
    {
        return $this->hasMany(PassChargeRequest::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function usesCustomerPhoneApproval(): bool
    {
        return $this->approval_channel === self::APPROVAL_CUSTOMER_PHONE;
    }

    public function requiresPin(): bool
    {
        return filled($this->pin_hash);
    }

    public function isPinLocked(): bool
    {
        return $this->pin_locked_until !== null && $this->pin_locked_until->isFuture();
    }

    public function pinAttemptsLeft(): int
    {
        if (! $this->requiresPin() || $this->isPinLocked()) {
            return 0;
        }

        return max(0, self::PIN_MAX_ATTEMPTS - (int) $this->pin_attempts);
    }

    public function pinLockSecondsLeft(): int
    {
        if (! $this->isPinLocked()) {
            return 0;
        }

        return max(0, $this->pin_locked_until->getTimestamp() - now()->getTimestamp());
    }

    public function verifyPin(string $pin): bool
    {
        return $this->requiresPin() && Hash::check($pin, $this->pin_hash);
    }

    public function setPin(string $pin): void
    {
        $this->forceFill([
            'pin_hash' => Hash::make($pin),
            'pin_set_at' => now(),
            'pin_attempts' => 0,
            'pin_locked_until' => null,
        ])->save();
    }

    public function removePin(): void
    {
        $this->forceFill([
            'pin_hash' => null,
            'pin_set_at' => null,
            'pin_attempts' => 0,
            'pin_locked_until' => null,
        ])->save();
    }

    public function unlockPin(): void
    {
        $this->forceFill([
            'pin_attempts' => 0,
            'pin_locked_until' => null,
        ])->save();
    }

    /**
     * Records a wrong PIN. Returns true when the pass is now locked.
     */
    public function registerFailedPin(): bool
    {
        $attempts = (int) $this->pin_attempts + 1;

        if ($attempts >= self::PIN_MAX_ATTEMPTS) {
            $this->forceFill([
                'pin_attempts' => 0,
                'pin_locked_until' => now()->addMinutes(self::PIN_LOCK_MINUTES),
            ])->save();

            return true;
        }

        $this->forceFill(['pin_attempts' => $attempts])->save();

        return false;
    }

    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $raw = '';
            for ($i = 0; $i < 8; $i++) {
                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = 'TRT-'.substr($raw, 0, 4).'-'.substr($raw, 4, 4);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Accepts "TRT-AB12-CD34", "trtab12cd34", or "AB12 CD34".
     */
    public static function normalizeCode(?string $input): ?string
    {
        $raw = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $input));

        if (str_starts_with($raw, 'TRT')) {
            $raw = substr($raw, 3);
        }

        if (strlen($raw) !== 8) {
            return null;
        }

        return 'TRT-'.substr($raw, 0, 4).'-'.substr($raw, 4, 4);
    }
}
