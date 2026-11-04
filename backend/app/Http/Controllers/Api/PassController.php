<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\PassChargeException;
use App\Http\Controllers\Controller;
use App\Models\Pass;
use App\Models\Route;
use App\Services\PassChargeService;
use App\Services\PassPinGate;
use App\Services\PassPresenter;
use App\Services\WalletService;
use Illuminate\Http\Request;

class PassController extends Controller
{
    public function __construct(
        private WalletService $wallets,
        private PassChargeService $charges,
        private PassPinGate $pins
    ) {
    }

    public function lookup(Request $request)
    {
        if (! $request->user()->isDriver() && ! $request->user()->isAdmin()) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'code' => 'required|string|max:30',
        ]);

        $code = Pass::normalizeCode($validated['code']);

        if (! $code) {
            return response()->json([
                'message' => 'That code does not look right. It should look like TRT-ABCD-2345.',
            ], 422);
        }

        $pass = Pass::with('user.wallet')->where('code', $code)->first();

        if (! $pass) {
            return response()->json([
                'message' => 'No pass found for that code. Check the card and try again.',
            ], 404);
        }

        $routes = Route::with([
            'origin',
            'destination',
            'fares' => fn ($query) => $query->where('is_active', true)->orderBy('amount'),
        ])
            ->orderBy('route_name')
            ->get()
            ->map(fn (Route $route) => [
                'id' => $route->id,
                'route_name' => $route->route_name,
                'origin' => $route->origin?->name,
                'destination' => $route->destination?->name,
                'fare' => $route->fares->isEmpty() ? null : round((float) $route->fares->first()->amount, 2),
                'fare_id' => $route->fares->first()?->id,
            ])
            ->values();

        return response()->json([
            'pass' => PassPresenter::present($pass),
            'routes' => $routes,
            'recent_rides' => $pass->rides()
                ->latest()
                ->limit(3)
                ->get(['id', 'destination_address', 'fare_amount', 'created_at']),
        ]);
    }

    public function charge(Request $request, Pass $pass)
    {
        $user = $request->user();

        if (! $user->isDriver() && ! $user->isAdmin()) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'route_id' => 'required|exists:routes,id',
            'fare_id' => 'nullable|exists:fares,id',
            'notes' => 'nullable|string|max:250',
            'pin' => 'nullable|digits:4',
        ]);

        $pass->load('user.wallet');

        if (! $pass->isActive()) {
            return response()->json([
                'message' => 'This pass is suspended. Ask an administrator to reactivate it.',
            ], 422);
        }

        $route = Route::with(['origin', 'destination'])->findOrFail($validated['route_id']);
        $fare = $this->charges->resolveFare($route, $validated['fare_id'] ?? null);

        if (! $fare || (float) $fare->amount <= 0) {
            return response()->json([
                'message' => 'This route has no active fare. Ask an administrator to set one.',
            ], 422);
        }

        $amount = round((float) $fare->amount, 2);

        if ($user->isDriver()) {
            $vehicle = $user->vehicle()->where('is_active', true)->first();

            if (! $vehicle) {
                return response()->json([
                    'message' => 'An approved, active vehicle is required to charge a pass.',
                ], 422);
            }
        }

        $recent = $this->charges->recentRide($pass);

        if ($recent) {
            return response()->json([
                'message' => 'This pass was just charged. Wait a moment before charging again.',
                'ride' => $recent,
            ], 409);
        }

        // The driver must get the customer's PIN before money leaves the pass.
        $pinApproved = false;

        if ($user->isDriver() && $pass->requiresPin()) {
            $failure = $this->pins->verify($pass, $validated['pin'] ?? null);

            if ($failure) {
                $status = $failure['status'];
                unset($failure['status']);

                return response()->json($failure, $status);
            }

            $pinApproved = true;
        }

        try {
            [$ride, $driverCredited] = $this->charges->execute(
                $pass,
                $route,
                $fare,
                $amount,
                $user->isDriver() ? $user : null,
                $user,
                $validated['notes'] ?? null,
                ['pin_approved' => $pinApproved]
            );
        } catch (InsufficientBalanceException $e) {
            $balance = $this->wallets->walletFor($pass->user);

            return response()->json([
                'message' => 'Not enough money on this pass. Load it before the trip.',
                'balance' => round((float) $balance->balance + (float) $balance->bonus_balance, 2),
                'fare' => $amount,
            ], 422);
        } catch (PassChargeException $e) {
            return response()->json(
                array_merge(['message' => $e->getMessage(), 'code' => $e->errorCode], $e->context),
                $e->status
            );
        }

        $pass->refresh()->load('user.wallet');

        return response()->json([
            'message' => 'GHS '.number_format($amount, 2).' charged to '.$pass->user->name.'.',
            'ride' => $ride,
            'pass' => PassPresenter::present($pass),
            'driver_credited' => $driverCredited,
        ], 201);
    }

    private function forbidden()
    {
        return response()->json(['message' => 'Forbidden.'], 403);
    }
}
