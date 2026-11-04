<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function walletFor(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'bonus_balance' => 0, 'pending_balance' => 0]
        );
    }

    public function credit(
        User $user,
        float $amount,
        string $type,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = []
    ): Wallet {
        $amount = $this->money($amount);

        return DB::transaction(function () use ($user, $amount, $type, $description, $referenceType, $referenceId, $metadata) {
            $wallet = $this->lockWallet($user);
            $wallet->balance = $this->money((float) $wallet->balance + $amount);
            $wallet->last_transaction_at = now();
            $wallet->save();

            $this->record($user, $wallet, $type, $amount, $description, $referenceType, $referenceId, $metadata);

            return $wallet;
        });
    }

    public function debit(
        User $user,
        float $amount,
        string $type,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = [],
        bool $allowBonus = true
    ): Wallet {
        $amount = $this->money($amount);

        return DB::transaction(function () use ($user, $amount, $type, $description, $referenceType, $referenceId, $metadata, $allowBonus) {
            $wallet = $this->lockWallet($user);
            $balance = (float) $wallet->balance;
            $bonus = $allowBonus ? (float) $wallet->bonus_balance : 0.0;

            if ($this->money($balance + $bonus) < $amount) {
                throw new InsufficientBalanceException;
            }

            $remaining = $amount;

            if ($allowBonus && $bonus > 0) {
                $bonusUsed = min($bonus, $remaining);
                $wallet->bonus_balance = $this->money($bonus - $bonusUsed);
                $remaining = $this->money($remaining - $bonusUsed);
            }

            $wallet->balance = $this->money($balance - $remaining);
            $wallet->last_transaction_at = now();
            $wallet->save();

            $this->record($user, $wallet, $type, -$amount, $description, $referenceType, $referenceId, $metadata);

            return $wallet;
        });
    }

    public function creditBonus(User $user, float $amount, string $description, ?int $bonusId = null): Wallet
    {
        $amount = $this->money($amount);

        return DB::transaction(function () use ($user, $amount, $description, $bonusId) {
            $wallet = $this->lockWallet($user);
            $wallet->bonus_balance = $this->money((float) $wallet->bonus_balance + $amount);
            $wallet->last_transaction_at = now();
            $wallet->save();

            $this->record(
                $user,
                $wallet,
                'bonus',
                $amount,
                $description,
                $bonusId ? 'wallet_bonus' : null,
                $bonusId
            );

            return $wallet;
        });
    }

    public function holdForWithdrawal(User $user, Withdrawal $withdrawal): Wallet
    {
        $amount = $this->money((float) $withdrawal->amount);

        return DB::transaction(function () use ($user, $amount, $withdrawal) {
            $wallet = $this->lockWallet($user);

            if ((float) $wallet->balance < $amount) {
                throw new InsufficientBalanceException;
            }

            $wallet->balance = $this->money((float) $wallet->balance - $amount);
            $wallet->pending_balance = $this->money((float) $wallet->pending_balance + $amount);
            $wallet->last_transaction_at = now();
            $wallet->save();

            $this->record(
                $user,
                $wallet,
                'withdrawal_hold',
                -$amount,
                "Withdrawal #{$withdrawal->id} requested",
                'withdrawal',
                $withdrawal->id
            );

            return $wallet;
        });
    }

    public function releaseWithdrawal(Withdrawal $withdrawal): Wallet
    {
        $user = $withdrawal->user;
        $amount = $this->money((float) $withdrawal->amount);

        return DB::transaction(function () use ($user, $amount, $withdrawal) {
            $wallet = $this->lockWallet($user);

            $wallet->pending_balance = $this->money(max(0, (float) $wallet->pending_balance - $amount));
            $wallet->balance = $this->money((float) $wallet->balance + $amount);
            $wallet->last_transaction_at = now();
            $wallet->save();

            $this->record(
                $user,
                $wallet,
                'withdrawal_rejected',
                $amount,
                "Withdrawal #{$withdrawal->id} returned",
                'withdrawal',
                $withdrawal->id
            );

            return $wallet;
        });
    }

    public function settleWithdrawal(Withdrawal $withdrawal): Wallet
    {
        $user = $withdrawal->user;
        $amount = $this->money((float) $withdrawal->amount);

        return DB::transaction(function () use ($user, $amount, $withdrawal) {
            $wallet = $this->lockWallet($user);

            $wallet->pending_balance = $this->money(max(0, (float) $wallet->pending_balance - $amount));
            $wallet->last_transaction_at = now();
            $wallet->save();

            $this->record(
                $user,
                $wallet,
                'withdrawal_paid',
                0,
                "Withdrawal #{$withdrawal->id} paid out",
                'withdrawal',
                $withdrawal->id
            );

            return $wallet;
        });
    }

    public function adjust(User $user, float $balance, float $bonusBalance, ?User $admin = null): Wallet
    {
        $balance = $this->money($balance);
        $bonusBalance = $this->money($bonusBalance);

        return DB::transaction(function () use ($user, $balance, $bonusBalance, $admin) {
            $wallet = $this->lockWallet($user);
            $difference = $this->money($balance - (float) $wallet->balance);

            $wallet->balance = $balance;
            $wallet->bonus_balance = $bonusBalance;
            $wallet->last_transaction_at = now();
            $wallet->save();

            $this->record(
                $user,
                $wallet,
                'adjustment',
                $difference,
                'Wallet adjusted by administrator',
                'user',
                $admin?->id,
                ['admin_id' => $admin?->id, 'bonus_balance' => $bonusBalance]
            );

            return $wallet;
        });
    }

    private function lockWallet(User $user): Wallet
    {
        $this->walletFor($user);

        return Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
    }

    private function record(
        User $user,
        Wallet $wallet,
        string $type,
        float $amount,
        string $description,
        ?string $referenceType,
        ?int $referenceId,
        array $metadata = []
    ): WalletTransaction {
        return WalletTransaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $this->money($amount),
            'balance_after' => $this->money((float) $wallet->balance + (float) $wallet->bonus_balance),
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function money(float $amount): float
    {
        return round($amount, 2);
    }
}
