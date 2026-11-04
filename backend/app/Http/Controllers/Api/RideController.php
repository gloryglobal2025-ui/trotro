<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SettingHelper;
use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\Route;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RideController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $rides = Ride::with(['passenger', 'driver', 'vehicle'])
                ->latest()
                ->get();
        } elseif ($user->isPassenger()) {
            $rides = Ride::where('passenger_id', $user->id)
                ->with(['driver', 'vehicle'])
                ->latest()
                ->get();
        } else {
            $rides = Ride::where('driver_id', $user->id)
                ->with(['passenger', 'vehicle'])
                ->latest()
                ->get();
        }

        return response()->json($rides);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_latitude' => 'required|numeric|between:-90,90',
            'origin_longitude' => 'required|numeric|between:-180,180',
            'origin_address' => 'required|string',
            'destination_latitude' => 'required|numeric|between:-90,90',
            'destination_longitude' => 'required|numeric|between:-180,180',
            'destination_address' => 'required|string',
            'payment_method' => ['required', Rule::in(SettingHelper::getPaymentMethods())],
            'route_id' => 'nullable|required_with:origin_stop_id,destination_stop_id|integer|exists:routes,id',
            'origin_stop_id' => 'nullable|required_with:route_id,destination_stop_id|integer|exists:bus_stops,id',
            'destination_stop_id' => 'nullable|required_with:route_id,origin_stop_id|integer|exists:bus_stops,id|different:origin_stop_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $distance = $this->calculateDistance(
            $request->origin_latitude,
            $request->origin_longitude,
            $request->destination_latitude,
            $request->destination_longitude
        );

        $route = null;
        $fare = null;

        if ($request->filled('route_id')) {
            $route = Route::with('stops')->findOrFail($request->route_id);
            $fare = $route->fareBetween(
                (int) $request->origin_stop_id,
                (int) $request->destination_stop_id
            );

            if ($fare === null) {
                return response()->json([
                    'message' => 'These stops are not served by this route in that order.',
                ], 422);
            }

            if ($fare <= 0) {
                return response()->json([
                    'message' => 'No fare has been set for this trip yet.',
                ], 422);
            }
        }

        $fare ??= $this->calculateFare($distance);
        $duration = $route?->estimated_duration ?? round($distance * 2);

        $ride = Ride::create([
            'passenger_id' => $request->user()->id,
            'route_id' => $route?->id,
            'origin_stop_id' => $route ? (int) $request->origin_stop_id : null,
            'destination_stop_id' => $route ? (int) $request->destination_stop_id : null,
            'origin_latitude' => $request->origin_latitude,
            'origin_longitude' => $request->origin_longitude,
            'origin_address' => $request->origin_address,
            'destination_latitude' => $request->destination_latitude,
            'destination_longitude' => $request->destination_longitude,
            'destination_address' => $request->destination_address,
            'status' => 'pending',
            'requested_at' => now(),
            'distance_km' => $distance,
            'duration_minutes' => $duration,
            'fare_amount' => $fare,
            'payment_method' => $request->payment_method,
        ]);

        return response()->json($ride->load(['passenger']), 201);
    }

    public function show(Request $request, Ride $ride)
    {
        $this->authorizeParticipant($request->user(), $ride);

        $ride->load(['passenger', 'driver', 'vehicle']);

        return response()->json($ride);
    }

    public function update(Request $request, Ride $ride)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:pending,accepted,driver_arrived,in_progress,completed,cancelled',
            'driver_current_latitude' => 'sometimes|required|numeric|between:-90,90',
            'driver_current_longitude' => 'sometimes|required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = $request->input('status');

        if ($user->isPassenger()) {
            if ($ride->passenger_id !== $user->id) {
                return $this->forbidden();
            }

            if ($status !== 'cancelled') {
                return response()->json(['message' => 'Passengers can only cancel their own rides.'], 403);
            }

            if (! in_array($ride->status, ['pending', 'accepted'], true)) {
                return response()->json(['message' => 'This ride can no longer be cancelled.'], 422);
            }

            $ride->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return response()->json($ride->load(['passenger', 'driver', 'vehicle']));
        }

        if ($user->isDriver()) {
            if ($status === 'accepted' && $ride->driver_id === null) {
                if ($ride->status !== 'pending') {
                    return response()->json(['message' => 'This ride is no longer available.'], 422);
                }

                $vehicle = $user->vehicle()->where('is_active', true)->first();

                if (! $vehicle) {
                    return response()->json(['message' => 'An approved, active vehicle is required to accept rides.'], 422);
                }

                $ride->update([
                    'driver_id' => $user->id,
                    'vehicle_id' => $vehicle->id,
                    'status' => 'accepted',
                    'accepted_at' => now(),
                ]);

                return response()->json($ride->load(['passenger', 'driver', 'vehicle']));
            }

            if ($ride->driver_id !== $user->id) {
                return $this->forbidden();
            }

            $transitions = [
                'accepted' => ['driver_arrived', 'cancelled'],
                'driver_arrived' => ['in_progress', 'cancelled'],
                'in_progress' => ['completed', 'cancelled'],
            ];

            if ($status !== null && ! in_array($status, $transitions[$ride->status] ?? [], true)) {
                return response()->json(['message' => 'Invalid status transition.'], 422);
            }
        }

        if (! $user->isAdmin() && ! $user->isDriver()) {
            return $this->forbidden();
        }

        // Admin, or an assigned driver updating their own ride.
        $ride->fill($request->only([
            'status',
            'driver_current_latitude',
            'driver_current_longitude',
        ]));

        if ($request->has('driver_current_latitude') && $request->has('driver_current_longitude')) {
            $ride->driver_last_location_update = now();
        }

        if ($request->status === 'accepted' && ! $ride->accepted_at) {
            $ride->accepted_at = now();
        }
        if ($request->status === 'driver_arrived' && ! $ride->pickup_at) {
            $ride->pickup_at = now();
        }
        if ($request->status === 'completed' && ! $ride->completed_at) {
            $ride->dropoff_at = now();
            $ride->completed_at = now();
        }
        if ($request->status === 'cancelled' && ! $ride->cancelled_at) {
            $ride->cancelled_at = now();
        }

        $ride->save();

        return response()->json($ride->load(['passenger', 'driver', 'vehicle']));
    }

    public function rate(Request $request, Ride $ride)
    {
        $user = $request->user();
        $this->authorizeParticipant($user, $ride);

        if ($ride->status !== 'completed') {
            return response()->json(['message' => 'Only completed rides can be rated.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($user->isPassenger() && $ride->passenger_id === $user->id) {
            if ($ride->driver_rating !== null) {
                return response()->json(['message' => 'This ride has already been rated.'], 422);
            }
            if (! $ride->driver) {
                return response()->json(['message' => 'This ride has no driver to rate.'], 422);
            }

            $ride->update([
                'driver_rating' => $request->rating,
                'driver_comment' => $request->comment,
            ]);

            $driver = $ride->driver;
            $driver->update([
                'rating' => $this->calculateAverageRating($driver->id, 'driver'),
                'total_rides' => $driver->total_rides + 1,
            ]);
        } elseif ($user->isDriver() && $ride->driver_id === $user->id) {
            if ($ride->passenger_rating !== null) {
                return response()->json(['message' => 'This ride has already been rated.'], 422);
            }

            $ride->update([
                'passenger_rating' => $request->rating,
                'passenger_comment' => $request->comment,
            ]);

            $passenger = $ride->passenger;
            $passenger->update([
                'rating' => $this->calculateAverageRating($passenger->id, 'passenger'),
                'total_rides' => $passenger->total_rides + 1,
            ]);
        } else {
            return $this->forbidden();
        }

        return response()->json($ride);
    }

    public function availableRides(Request $request)
    {
        if (! $request->user()->isDriver() && ! $request->user()->isAdmin()) {
            return $this->forbidden();
        }

        $rides = Ride::where('status', 'pending')
            ->whereNull('driver_id')
            ->with('passenger')
            ->latest()
            ->get();

        return response()->json($rides);
    }

    public function findNearbyDrivers(Request $request, Ride $ride)
    {
        $this->authorizeParticipant($request->user(), $ride);

        $drivers = User::where('role', 'driver')
            ->whereHas('vehicle', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        $nearbyDrivers = $drivers->map(function (User $driver) {
            return [
                'driver' => $driver->load('vehicle'),
                'distance' => 0,
            ];
        });

        return response()->json($nearbyDrivers);
    }

    public function updateDriverLocation(Request $request, Ride $ride)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $ride->driver_id !== $user->id) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ride->update([
            'driver_current_latitude' => $request->latitude,
            'driver_current_longitude' => $request->longitude,
            'driver_last_location_update' => now(),
        ]);

        return response()->json([
            'success' => true,
            'location' => [
                'latitude' => $ride->driver_current_latitude,
                'longitude' => $ride->driver_current_longitude,
                'updated_at' => $ride->driver_last_location_update,
            ],
        ]);
    }

    private function authorizeParticipant(User $user, Ride $ride): void
    {
        abort_unless(
            $user->isAdmin() || $ride->passenger_id === $user->id || $ride->driver_id === $user->id,
            403,
            'You cannot access this ride.'
        );
    }

    private function forbidden()
    {
        return response()->json(['message' => 'Forbidden.'], 403);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function calculateFare($distance)
    {
        $baseFare = SettingHelper::getBaseFare();
        $ratePerKm = SettingHelper::getFarePerKm();
        $minFare = SettingHelper::getMinFare();

        $multiplier = SettingHelper::isPeakHour() ? SettingHelper::getPeakHourMultiplier() : 1.0;

        $fare = ($baseFare + ($distance * $ratePerKm)) * $multiplier;

        return max(round($fare, 2), $minFare);
    }

    private function calculateAverageRating($userId, $type)
    {
        $column = $type === 'driver' ? 'driver_rating' : 'passenger_rating';

        $ratings = Ride::where($type === 'driver' ? 'driver_id' : 'passenger_id', $userId)
            ->whereNotNull($column)
            ->pluck($column);

        if ($ratings->isEmpty()) {
            return 5.0;
        }

        return round($ratings->avg(), 1);
    }
}
