<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pass;
use App\Models\PassChargeRequest;
use App\Models\Route;
use App\Services\PassChargeService;
use App\Services\PassPresenter;
use App\Services\SmsService;
use Illuminate\Http\Request;

class PassChargeRequestController extends Controller
{
    public function __construct(
        private PassChargeService $charges,
        private SmsService $sms
    ) {
    }

    public function store(Request $request, Pass $pass)
    {
        $user = $request->user();

        if (! $user->isDriver() && ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'route_id' => 'required|exists:routes,id',
            'fare_id' => 'nullable|exists:fares,id',
            'notes' => 'nullable|string|max:250',
        ]);

        $pass->load('user.wallet');

        if (! $pass->isActive()) {
            return response()->json([
                'message' => 'This pass is suspended. Ask an administrator to reactivate it.',
            ], 422);
        }

        if (! $pass->usesCustomerPhoneApproval()) {
            return response()->json([
                'message' => 'This customer approves on the driver phone. Use the PIN keypad instead.',
                'code' => 'approval_channel_required',
            ], 422);
        }

        if (! $pass->requiresPin()) {
            return response()->json([
                'message' => 'This pass has no customer PIN. Charge it directly.',
                'code' => 'pin_not_required',
            ], 422);
        }

        if ($pass->isPinLocked()) {
            return response()->json([
                'message' => 'This pass is locked after too many wrong PINs. Ask an administrator to unlock it.',
                'code' => 'pin_locked',
            ], 423);
        }

        $phone = SmsService::normalizePhone($pass->user->phone);

        if ($phone === null) {
            return response()->json([
                'message' => 'Add a valid phone number to this pass before using phone approval.',
                'code' => 'phone_missing',
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
                'code' => 'pass_already_charged',
            ], 409);
        }

        $destination = $route->destination?->name ?? $route->route_name;

        PassChargeRequest::where('pass_id', $pass->id)
            ->where('status', PassChargeRequest::STATUS_PENDING)
            ->update([
                'status' => PassChargeRequest::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'failure_reason' => 'Replaced by a newer approval request.',
            ]);

        $chargeRequest = PassChargeRequest::create([
            'pass_id' => $pass->id,
            'driver_id' => $user->isDriver() ? $user->id : null,
            'route_id' => $route->id,
            'fare_id' => $fare->id,
            'amount' => $amount,
            'customer_phone' => $phone,
            'destination' => $destination,
            'status' => PassChargeRequest::STATUS_PENDING,
            'channel' => PassChargeRequest::CHANNEL_SMS,
            'expires_at' => now()->addSeconds(PassChargeRequest::TTL_SECONDS),
        ]);

        $minutes = (int) ceil(PassChargeRequest::TTL_SECONDS / 60);
        $text = 'Trotro: GHS '.number_format($amount, 2)." to {$destination} for {$pass->user->name}. "
            ."Reply with your 4-digit PIN to approve, or 0 to cancel. Expires in {$minutes} min.";

        try {
            $this->sms->send($phone, $text);
        } catch (\Throwable $e) {
            $chargeRequest->forceFill([
                'status' => PassChargeRequest::STATUS_FAILED,
                'failure_reason' => 'The approval SMS could not be sent.',
            ])->save();

            report($e);

            return response()->json([
                'message' => 'Could not send the approval SMS. Try again or approve on this phone.',
                'code' => 'sms_failed',
            ], 502);
        }

        return response()->json([
            'message' => 'Approval request sent to the customer phone.',
            'request' => $this->presentRequest($chargeRequest->fresh()),
            'pass' => PassPresenter::present($pass->refresh()->load('user.wallet')),
        ], 201);
    }

    public function show(Request $request, PassChargeRequest $passChargeRequest)
    {
        if (! $this->canView($request, $passChargeRequest)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $passChargeRequest->markExpired();
        $passChargeRequest->load(['ride', 'pass.user.wallet']);

        return response()->json([
            'request' => $this->presentRequest($passChargeRequest),
            'pass' => PassPresenter::present($passChargeRequest->pass),
            'ride' => $passChargeRequest->ride,
        ]);
    }

    public function cancel(Request $request, PassChargeRequest $passChargeRequest)
    {
        if (! $this->canView($request, $passChargeRequest)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($passChargeRequest->status === PassChargeRequest::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'This trip was already approved and charged.',
                'code' => 'request_completed',
            ], 422);
        }

        $cancelled = PassChargeRequest::whereKey($passChargeRequest->id)
            ->where('status', PassChargeRequest::STATUS_PENDING)
            ->update([
                'status' => PassChargeRequest::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'failure_reason' => 'Cancelled by the driver.',
                'updated_at' => now(),
            ]);

        $passChargeRequest->refresh();

        if ($cancelled === 0 && $passChargeRequest->status === PassChargeRequest::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'This trip was already approved and charged.',
                'code' => 'request_completed',
            ], 422);
        }

        return response()->json([
            'message' => 'Approval request cancelled.',
            'request' => $this->presentRequest($passChargeRequest->fresh()),
        ]);
    }

    private function canView(Request $request, PassChargeRequest $chargeRequest): bool
    {
        $user = $request->user();

        return $user->isAdmin()
            || ($chargeRequest->driver_id !== null && $user->id === $chargeRequest->driver_id);
    }

    private function presentRequest(PassChargeRequest $chargeRequest): array
    {
        return [
            'id' => $chargeRequest->id,
            'status' => $chargeRequest->status,
            'channel' => $chargeRequest->channel,
            'amount' => round((float) $chargeRequest->amount, 2),
            'destination' => $chargeRequest->destination,
            'customer_phone' => SmsService::maskPhone($chargeRequest->customer_phone),
            'expires_at' => $chargeRequest->expires_at,
            'seconds_left' => $chargeRequest->secondsLeft(),
            'approved_at' => $chargeRequest->approved_at,
            'declined_at' => $chargeRequest->declined_at,
            'cancelled_at' => $chargeRequest->cancelled_at,
            'completed_at' => $chargeRequest->completed_at,
            'failure_reason' => $chargeRequest->failure_reason,
            'ride_id' => $chargeRequest->ride_id,
        ];
    }
}
