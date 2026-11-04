<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'is_assisted',
        'profile_image',
        'rating',
        'total_rides',
        'user_level_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'profile_image_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rating' => 'float',
            'is_assisted' => 'boolean',
        ];
    }

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class, 'driver_id');
    }

    public function ridesAsPassenger()
    {
        return $this->hasMany(Ride::class, 'passenger_id');
    }

    public function ridesAsDriver()
    {
        return $this->hasMany(Ride::class, 'driver_id');
    }

    public function userLevel()
    {
        return $this->belongsTo(UserLevel::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function pass()
    {
        return $this->hasOne(Pass::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function walletDeposits()
    {
        return $this->hasMany(WalletDeposit::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'bonus_balance' => 0, 'pending_balance' => 0]
            );
        });
    }

    public function isDriver()
    {
        return $this->role === 'driver';
    }

    public function isPassenger()
    {
        return $this->role === 'passenger';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        return Image::url($this->profile_image);
    }
}
