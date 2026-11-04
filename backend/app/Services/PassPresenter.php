<?php

namespace App\Services;

use App\Models\Pass;

class PassPresenter
{
    public static function present(Pass $pass): array
    {
        $wallet = $pass->user->wallet;

        return [
            'id' => $pass->id,
            'code' => $pass->code,
            'status' => $pass->status,
            'approval_channel' => $pass->approval_channel ?? Pass::APPROVAL_DEVICE_PIN,
            'name' => $pass->user->name,
            'phone' => $pass->user->phone,
            'balance' => round((float) ($wallet->balance ?? 0) + (float) ($wallet->bonus_balance ?? 0), 2),
            'ride_count' => $pass->ride_count,
            'last_used_at' => $pass->last_used_at,
            'currency' => 'GHS',
            'pin_required' => $pass->requiresPin(),
            'pin_locked' => $pass->isPinLocked(),
            'pin_attempts_left' => $pass->pinAttemptsLeft(),
        ];
    }
}
