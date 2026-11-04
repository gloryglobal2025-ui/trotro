<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\PassChargeException;
use App\Http\Controllers\Controller;
use App\Models\PassChargeRequest;
use App\Services\PassChargeService;
use App\Services\PassPinGate;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsWebhookController extends Controller
{
    public function __construct(
        private PassChargeService $charges,
        private PassPinGate $pins,
        private SmsService $sms
    ) {
    }

    public function inbound(Request $request)
    {
        $phone = SmsService::normalizePhone($this->sender($request));

        if ($phone === null) {
            return response()->json(['message' => 'Unknown sender.'], 422);
        }

        $text = trim((string) $this->firstInput($request, ['text', 'message', 'content']));
        $chargeRequest = $this->pendingRequest($phone);

        if (! $chargeRequest) {
            $message = 'No trip is waiting for approval.';
            $this->reply($phone, $message);

            return response()->json(['message' => $message]);
        }

        $result = $this->handleCustomerInput($chargeRequest, $text, PassChargeRequest::CHANNEL_SMS);
        $this->reply($phone, $result['message']);

        return response()->json([
            'message' => $result['message'],
            'request_id' => $result['request']?->id,
            'status' => $result['request']?->status,
        ]);
    }

    public function ussd(Request $request)
    {
        $phone = SmsService::normalizePhone($this->sender($request));

        if ($phone === null) {
            return response()->json([
                'message' => 'Unknown phone number.',
                'continue' => false,
            ], 422);
        }

        $text = trim((string) $request->input('text', ''));

        if (str_contains($text, '*')) {
            $parts = explode('*', $text);
            $text = trim((string) end($parts));
        }

        $chargeRequest = $this->pendingRequest($phone);

        if (! $chargeRequest) {
            return response()->json([
                'message' => 'Trotro: no trip is waiting for approval.',
                'continue' => false,
            ]);
        }

        if ($text === '') {
            return response()->json([
                'message' => $this->prompt($chargeRequest),
                'continue' => true,
                'request_id' => $chargeRequest->id,
                'status' => $chargeRequest->status,
            ]);
        }

        $result = $this->handleCustomerInput($chargeRequest, $text, PassChargeRequest::CHANNEL_USSD);

        return response()->json([
            'message' => $result['message'],
            'continue' => $result['request']?->status === PassChargeRequest::STATUS_PENDING,
            'request_id' => $result['request']?->id,
            'status' => $result['request']?->status,
        ]);
    }

    /**
     * @return array{message:string, request:?PassChargeRequest}
     */
    private function handleCustomerInput(PassChargeRequest $chargeRequest, string $raw, string $channel): array
    {
        $normalized = strtoupper(trim($raw));

        if ($normalized === '0' || in_array($normalized, ['NO', 'CANCEL', 'STOP'], true)) {
            return $this->decline($chargeRequest);
        }

        $digits = preg_replace('/\D+/', '', $normalized);

        if (strlen($digits) !== 4 || preg_match('/[A-Z]/', $normalized)) {
            return [
                'message' => 'Reply with the 4-digit PIN to approve, or 0 to cancel.',
                'request' => $chargeRequest,
            ];
        }

        return $this->approve($chargeRequest, $digits, $channel);
    }

    /**
     * @return array{message:string, request:?PassChargeRequest}
     */
    private function approve(PassChargeRequest $chargeRequest, string $pin, string $channel): array
    {
        return DB::transaction(function () use ($chargeRequest, $pin, $channel) {
            $locked = PassChargeRequest::with([
                'pass.user.wallet',
                'route.origin',
                'route.destination',
                'fare',
                'driver',
            ])->whereKey($chargeRequest->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== PassChargeRequest::STATUS_PENDING) {
                return ['message' => 'This trip was already handled.', 'request' => $locked];
            }

            if ($locked->expires_at->isPast()) {
                $locked->forceFill([
                    'status' => PassChargeRequest::STATUS_EXPIRED,
                    'failure_reason' => 'The customer did not answer in time.',
                ])->save();

                return ['message' => 'This approval request has expired. No money was taken.', 'request' => $locked];
            }

            $pass = $locked->pass;

            if (! $pass->isActive()) {
                $locked->forceFill([
                    'status' => PassChargeRequest::STATUS_FAILED,
                    'failure_reason' => 'This pass is suspended.',
                ])->save();

                return ['message' => 'This pass is suspended. No money was taken.', 'request' => $locked];
            }

            $failure = $this->pins->verify($pass, $pin);

            if ($failure) {
                $message = match ($failure['code']) {
                    'pin_locked' => 'Too many wrong PINs. The pass is locked for a while. No money was taken.',
                    'pin_invalid' => 'Wrong PIN. '.$failure['attempts_left'].' '
                        .($failure['attempts_left'] === 1 ? 'try' : 'tries').' left. Reply again.',
                    default => $failure['message'],
                };

                return ['message' => $message, 'request' => $locked->fresh()];
            }

            if (! $locked->route || ! $locked->fare) {
                $locked->forceFill([
                    'status' => PassChargeRequest::STATUS_FAILED,
                    'failure_reason' => 'The route or fare is no longer available.',
                ])->save();

                return ['message' => 'The route or fare is no longer available. No money was taken.', 'request' => $locked];
            }

            try {
                [$ride] = $this->charges->execute(
                    $pass,
                    $locked->route,
                    $locked->fare,
                    (float) $locked->amount,
                    $locked->driver,
                    $locked->driver ?? $pass->user,
                    'Approved by customer PIN via '.$channel,
                    [
                        'pin_approved' => true,
                        'remote_approval' => true,
                        'approval_channel' => $channel,
                        'charge_request_id' => $locked->id,
                    ]
                );
            } catch (InsufficientBalanceException $e) {
                $locked->forceFill([
                    'status' => PassChargeRequest::STATUS_FAILED,
                    'failure_reason' => 'Not enough money on this pass.',
                ])->save();

                return ['message' => 'Not enough money on this pass. No money was taken.', 'request' => $locked];
            } catch (PassChargeException $e) {
                $locked->forceFill([
                    'status' => PassChargeRequest::STATUS_FAILED,
                    'failure_reason' => $e->getMessage(),
                ])->save();

                return ['message' => $e->getMessage(), 'request' => $locked];
            }

            $locked->forceFill([
                'status' => PassChargeRequest::STATUS_COMPLETED,
                'approved_at' => now(),
                'completed_at' => now(),
                'ride_id' => $ride->id,
            ])->save();

            $wallet = $pass->user->wallet()->first();
            $remaining = round((float) ($wallet->balance ?? 0) + (float) ($wallet->bonus_balance ?? 0), 2);

            return [
                'message' => 'Approved. GHS '.number_format((float) $locked->amount, 2)
                    .' charged. GHS '.number_format($remaining, 2).' left on the pass.',
                'request' => $locked,
            ];
        });
    }

    /**
     * @return array{message:string, request:?PassChargeRequest}
     */
    private function decline(PassChargeRequest $chargeRequest): array
    {
        return DB::transaction(function () use ($chargeRequest) {
            $locked = PassChargeRequest::whereKey($chargeRequest->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== PassChargeRequest::STATUS_PENDING) {
                return ['message' => 'This trip was already handled.', 'request' => $locked];
            }

            $locked->forceFill([
                'status' => PassChargeRequest::STATUS_DECLINED,
                'declined_at' => now(),
                'failure_reason' => 'Declined by the customer.',
            ])->save();

            return ['message' => 'Trip cancelled. No money was taken.', 'request' => $locked];
        });
    }

    private function pendingRequest(string $phone): ?PassChargeRequest
    {
        PassChargeRequest::where('customer_phone', $phone)
            ->where('status', PassChargeRequest::STATUS_PENDING)
            ->where('expires_at', '<=', now())
            ->update([
                'status' => PassChargeRequest::STATUS_EXPIRED,
                'failure_reason' => 'The customer did not answer in time.',
            ]);

        return PassChargeRequest::with(['pass.user.wallet', 'route.destination', 'fare', 'driver'])
            ->where('customer_phone', $phone)
            ->where('status', PassChargeRequest::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    private function prompt(PassChargeRequest $chargeRequest): string
    {
        $destination = $chargeRequest->destination ?? $chargeRequest->route?->destination?->name ?? 'your trip';

        return 'Trotro: GHS '.number_format((float) $chargeRequest->amount, 2).' to '.$destination
            .' for '.$chargeRequest->pass?->user?->name.'. Enter your 4-digit PIN to approve, or 0 to cancel.';
    }

    private function sender(Request $request): string
    {
        return (string) $this->firstInput($request, ['from', 'msisdn', 'sender', 'phone']);
    }

    private function firstInput(Request $request, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $request->input($key);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function reply(string $phone, string $message): void
    {
        try {
            $this->sms->send($phone, $message);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
