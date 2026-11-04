<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\PaymentException;
use App\Helpers\SettingHelper;
use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private WalletService $wallets)
    {
    }

    public function processPayment(Request $request, Ride $ride)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $ride->passenger_id !== $user->id) {
            return $this->forbidden();
        }

        $enabledMethods = array_values(array_unique(array_merge(
            SettingHelper::getPaymentMethods(),
            ['cash', 'wallet']
        )));

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:'.implode(',', $enabledMethods)],
        ]);

        if ($ride->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This ride has already been paid.',
            ], 422);
        }

        if ($ride->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Payment can only be recorded after the ride is completed.',
            ], 422);
        }

        if ($validated['payment_method'] === 'wallet') {
            return $this->processWalletPayment($ride);
        }

        if ($validated['payment_method'] !== 'cash') {
            return response()->json([
                'success' => false,
                'message' => 'Online payments are not available yet.',
            ], 400);
        }

        $transactionId = 'CASH_'.strtoupper(uniqid());
        $paymentReference = 'REF_'.strtoupper(uniqid());

        $ride->update([
            'payment_status' => 'paid',
            'payment_method' => $validated['payment_method'],
            'transaction_id' => $transactionId,
            'payment_reference' => $paymentReference,
            'paid_at' => now(),
            'payment_metadata' => [
                'method' => $validated['payment_method'],
                'recorded_by' => $user->id,
                'processed_at' => now()->toIso8601String(),
                'driver_credited' => false,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cash payment recorded successfully.',
            'transaction_id' => $transactionId,
            'payment_reference' => $paymentReference,
            'ride' => $ride->load(['passenger', 'driver', 'vehicle']),
        ]);
    }

    private function processWalletPayment(Ride $ride)
    {
        try {
            $transactionId = DB::transaction(function () use ($ride) {
                $locked = Ride::whereKey($ride->id)->lockForUpdate()->firstOrFail();

                if ($locked->payment_status === 'paid') {
                    throw new PaymentException('This ride has already been paid.');
                }

                $fare = round((float) $locked->fare_amount, 2);

                if ($fare <= 0) {
                    throw new PaymentException('This ride has no fare to pay.');
                }

                $this->wallets->debit(
                    $locked->passenger,
                    $fare,
                    'ride_payment',
                    "Ride #{$locked->id} payment",
                    'ride',
                    $locked->id
                );

                $driverCredited = false;

                if ($locked->driver_id) {
                    $this->wallets->credit(
                        $locked->driver,
                        $fare,
                        'ride_earning',
                        "Ride #{$locked->id} earnings",
                        'ride',
                        $locked->id
                    );
                    $driverCredited = true;
                }

                $transactionId = 'WALLET_'.strtoupper(uniqid());

                $locked->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'wallet',
                    'transaction_id' => $transactionId,
                    'payment_reference' => 'REF_'.strtoupper(uniqid()),
                    'paid_at' => now(),
                    'payment_metadata' => [
                        'method' => 'wallet',
                        'recorded_by' => $locked->passenger_id,
                        'processed_at' => now()->toIso8601String(),
                        'driver_credited' => $driverCredited,
                    ],
                ]);

                return $transactionId;
            });
        } catch (InsufficientBalanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (PaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet payment completed successfully.',
            'transaction_id' => $transactionId,
            'ride' => $ride->fresh()->load(['passenger', 'driver', 'vehicle']),
        ]);
    }

    public function getPaymentStatus(Request $request, Ride $ride)
    {
        $user = $request->user();

        if (! $user->isAdmin()
            && $ride->passenger_id !== $user->id
            && $ride->driver_id !== $user->id) {
            return $this->forbidden();
        }

        return response()->json([
            'ride_id' => $ride->id,
            'payment_status' => $ride->payment_status,
            'payment_method' => $ride->payment_method,
            'transaction_id' => $ride->transaction_id,
            'payment_reference' => $ride->payment_reference,
            'paid_at' => $ride->paid_at,
            'payment_metadata' => $ride->payment_metadata,
            'fare_amount' => $ride->fare_amount,
        ]);
    }

    public function refundPayment(Request $request, Ride $ride)
    {
        if ($ride->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Payment cannot be refunded.',
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        if ($ride->payment_method !== 'wallet') {
            $ride->update([
                'payment_status' => 'refunded',
                'payment_metadata' => array_merge($ride->payment_metadata ?? [], [
                    'refunded_at' => now()->toIso8601String(),
                    'refund_reason' => $validated['reason'],
                    'refunded_by' => $request->user()->id,
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Refund recorded successfully.',
                'ride' => $ride->load(['passenger', 'driver', 'vehicle']),
            ]);
        }

        try {
            DB::transaction(function () use ($ride, $validated, $request) {
                $locked = Ride::whereKey($ride->id)->lockForUpdate()->firstOrFail();
                $fare = round((float) $locked->fare_amount, 2);
                $metadata = $locked->payment_metadata ?? [];

                $this->wallets->credit(
                    $locked->passenger,
                    $fare,
                    'refund',
                    "Ride #{$locked->id} refund",
                    'ride',
                    $locked->id
                );

                if (($metadata['driver_credited'] ?? false) && $locked->driver_id) {
                    $this->wallets->debit(
                        $locked->driver,
                        $fare,
                        'refund_reversal',
                        "Ride #{$locked->id} refund reversal",
                        'ride',
                        $locked->id
                    );
                }

                $locked->update([
                    'payment_status' => 'refunded',
                    'payment_metadata' => array_merge($metadata, [
                        'refunded_at' => now()->toIso8601String(),
                        'refund_reason' => $validated['reason'],
                        'refunded_by' => $request->user()->id,
                    ]),
                ]);
            });
        } catch (InsufficientBalanceException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The driver wallet does not have enough balance to reverse this payment.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet refund completed successfully.',
            'ride' => $ride->fresh()->load(['passenger', 'driver', 'vehicle']),
        ]);
    }

    private function forbidden()
    {
        return response()->json(['message' => 'Forbidden.'], 403);
    }
}
