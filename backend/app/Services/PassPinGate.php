<?php

namespace App\Services;

use App\Models\Pass;
use Illuminate\Support\Facades\DB;

class PassPinGate
{
    /**
     * Verifies the customer PIN under a row lock so attempts cannot be raced.
     * Returns null when approved, otherwise a structured failure for the caller to render.
     *
     * @return array{status:int, code:string, message:string, attempts_left?:int, retry_after_minutes?:int}|null
     */
    public function verify(Pass $pass, ?string $pin): ?array
    {
        return DB::transaction(function () use ($pass, $pin) {
            $locked = Pass::whereKey($pass->id)->lockForUpdate()->firstOrFail();

            if (! $locked->requiresPin()) {
                return null;
            }

            if ($locked->isPinLocked()) {
                return $this->locked($locked);
            }

            if ($pin === null || $pin === '') {
                return [
                    'status' => 428,
                    'code' => 'pin_required',
                    'message' => 'Ask the customer to enter their 4-digit PIN to approve this trip.',
                ];
            }

            if (! $locked->verifyPin($pin)) {
                if ($locked->registerFailedPin()) {
                    return $this->locked($locked);
                }

                $left = $locked->pinAttemptsLeft();

                return [
                    'status' => 422,
                    'code' => 'pin_invalid',
                    'message' => 'That PIN is not correct. '.$left.' '.($left === 1 ? 'try' : 'tries').' left.',
                    'attempts_left' => $left,
                ];
            }

            if ($locked->pin_attempts > 0 || $locked->pin_locked_until !== null) {
                $locked->unlockPin();
            }

            return null;
        });
    }

    /**
     * @return array{status:int, code:string, message:string, retry_after_minutes:int}
     */
    private function locked(?Pass $pass = null): array
    {
        $seconds = $pass?->pinLockSecondsLeft() ?? Pass::PIN_LOCK_MINUTES * 60;
        $minutes = max(1, (int) ceil($seconds / 60));

        return [
            'status' => 423,
            'code' => 'pin_locked',
            'message' => "Too many wrong PINs. Try again in {$minutes} minute(s) or ask an administrator to unlock this pass.",
            'retry_after_minutes' => $minutes,
        ];
    }
}
