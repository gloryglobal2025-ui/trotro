<?php

namespace App\Services;

use App\Exceptions\PassChargeException;
use App\Models\Fare;
use App\Models\Pass;
use App\Models\Ride;
use App\Models\Route;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PassChargeService
{
    public const DUPLICATE_WINDOW_SECONDS = 45;

    public function __construct(private WalletService $wallets)
    {
    }

    public function resolveFare(Route $route, ?int $fareId = null): ?Fare
    {
        $query = $route->fares()->where('is_active', true);

        if ($fareId) {
            $query->whereKey($fareId);
        }

        return $query->orderBy('amount')->first();
    }

    public function recentRide(Pass $pass): ?Ride
    {
        return Ride::where('pass_id', $pass->id)
            ->where('created_at', '>=', now()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))
            ->latest()
            ->first();
    }

    /**
     * Charges a pass and records the completed ride. Throws PassChargeException for
     * business failures and InsufficientBalanceException when the wallet is short.
     *
     * @return array{0: Ride, 1: bool} ride and whether a driver was credited
     */
    public function execute(
        Pass $pass,
        Route $route,
        Fare $fare,
        float $amount,
        ?User $driver = null,
        ?User $recordedBy = null,
        ?string $notes = null,
        array $metadata = []
    ): array {
        $amount = round($amount, 2);

        return DB::transaction(function () use ($pass, $route, $fare, $amount, $driver, $recordedBy, $notes, $metadata) {
            $locked = Pass::whereKey($pass->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isActive()) {
                throw PassChargeException::suspended();
            }

            $recent = Ride::where('pass_id', $locked->id)
                ->where('created_at', '>=', now()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))
                ->latest()
                ->first();

            if ($recent) {
                throw PassChargeException::alreadyCharged($recent);
            }

            $vehicle = null;

            if ($driver && $driver->isDriver()) {
                $vehicle = $driver->vehicle()->where('is_active', true)->first();

                if (! $vehicle) {
                    throw new PassChargeException('An approved, active vehicle is required to charge a pass.');
                }
            }

            $recordedBy ??= $locked->user;

            $this->wallets->debit(
                $locked->user,
                $amount,
                'pass_ride',
                "Pass {$locked->code} trip to {$route->destination?->name}",
                'pass',
                $locked->id,
                ['route_id' => $route->id, 'fare_id' => $fare->id, 'driver_id' => $driver?->id]
            );

            $driverCredited = false;

            if ($driver && $driver->isDriver()) {
                $this->wallets->credit(
                    $driver,
                    $amount,
                    'ride_earning',
                    "Pass {$locked->code} trip earnings",
                    'pass',
                    $locked->id,
                    ['route_id' => $route->id, 'fare_id' => $fare->id]
                );
                $driverCredited = true;
            }

            $now = now();

            $ride = Ride::create([
                'passenger_id' => $locked->user_id,
                'driver_id' => $driver?->id,
                'vehicle_id' => $vehicle?->id,
                'pass_id' => $locked->id,
                'origin_latitude' => $route->origin?->latitude,
                'origin_longitude' => $route->origin?->longitude,
                'origin_address' => $route->origin?->name ?? $route->route_name,
                'destination_latitude' => $route->destination?->latitude,
                'destination_longitude' => $route->destination?->longitude,
                'destination_address' => $route->destination?->name ?? $route->route_name,
                'status' => 'completed',
                'requested_at' => $now,
                'pickup_at' => $now,
                'dropoff_at' => $now,
                'completed_at' => $now,
                'distance_km' => 0,
                'duration_minutes' => $route->estimated_duration,
                'fare_amount' => $amount,
                'payment_status' => 'paid',
                'payment_method' => 'wallet',
                'transaction_id' => 'PASS_'.strtoupper(uniqid()),
                'payment_reference' => 'REF_'.strtoupper(uniqid()),
                'paid_at' => $now,
                'payment_metadata' => array_merge([
                    'method' => 'pass',
                    'pass_id' => $locked->id,
                    'pass_code' => $locked->code,
                    'route_id' => $route->id,
                    'fare_id' => $fare->id,
                    'recorded_by' => $recordedBy?->id,
                    'notes' => $notes,
                    'processed_at' => $now->toIso8601String(),
                    'driver_credited' => $driverCredited,
                ], $metadata),
            ]);

            $locked->update([
                'ride_count' => $locked->ride_count + 1,
                'last_used_at' => $now,
            ]);

            return [$ride, $driverCredited];
        });
    }
}
