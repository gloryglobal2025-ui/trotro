<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function __construct(private WalletService $wallets)
    {
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $wallet = $this->wallets->walletFor($user);

        return response()->json([
            'wallet' => $this->present($wallet),
            'transactions' => $user->walletTransactions()->where('amount', '!=', 0)->latest()->limit(20)->get(),
            'deposits' => $user->walletDeposits()->latest()->limit(10)->get(),
            'withdrawals' => $user->withdrawals()->latest()->limit(10)->get(),
        ]);
    }

    public function transactions(Request $request)
    {
        return response()->json(
            $request->user()->walletTransactions()->where('amount', '!=', 0)->latest()->limit(100)->get()
        );
    }

    public function deposits(Request $request)
    {
        return response()->json(
            $request->user()->walletDeposits()->latest()->limit(50)->get()
        );
    }

    public function withdrawals(Request $request)
    {
        return response()->json(
            $request->user()->withdrawals()->latest()->limit(50)->get()
        );
    }

    public function requestDeposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
            'method' => 'required|in:momo,bank',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $deposit = $request->user()->walletDeposits()->create([
            'amount' => round((float) $validated['amount'], 2),
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Deposit request submitted. It will be credited after admin approval.',
            'deposit' => $deposit,
        ], 201);
    }

    public function requestWithdrawal(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
            'method' => 'required|in:momo,bank',
            'mobile_number' => 'required_if:method,momo|nullable|string|max:30',
            'bank_name' => 'required_if:method,bank|nullable|string|max:100',
            'account_number' => 'required_if:method,bank|nullable|string|max:50',
            'account_name' => 'required_if:method,bank|nullable|string|max:100',
        ]);

        try {
            $withdrawal = DB::transaction(function () use ($request, $validated) {
                $withdrawal = $request->user()->withdrawals()->create([
                    'amount' => round((float) $validated['amount'], 2),
                    'method' => $validated['method'],
                    'mobile_number' => $validated['mobile_number'] ?? null,
                    'bank_name' => $validated['bank_name'] ?? null,
                    'account_number' => $validated['account_number'] ?? null,
                    'account_name' => $validated['account_name'] ?? null,
                    'status' => 'pending',
                ]);

                $this->wallets->holdForWithdrawal($request->user(), $withdrawal);

                return $withdrawal;
            });
        } catch (InsufficientBalanceException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Withdrawal request submitted for approval.',
            'withdrawal' => $withdrawal,
            'wallet' => $this->present($this->wallets->walletFor($request->user())),
        ], 201);
    }

    private function present(Wallet $wallet): array
    {
        return [
            'balance' => round((float) $wallet->balance, 2),
            'bonus_balance' => round((float) $wallet->bonus_balance, 2),
            'pending_balance' => round((float) $wallet->pending_balance, 2),
            'available_balance' => $wallet->available_balance,
            'currency' => 'GHS',
            'last_transaction_at' => $wallet->last_transaction_at,
        ];
    }
}
