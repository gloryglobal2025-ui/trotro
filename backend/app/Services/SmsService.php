<?php

namespace App\Services;

use App\Contracts\SmsGateway;
use InvalidArgumentException;

class SmsService
{
    public function __construct(private SmsGateway $gateway)
    {
    }

    public function send(string $to, string $message): void
    {
        $phone = self::normalizePhone($to);

        if ($phone === null) {
            throw new InvalidArgumentException('Enter a valid Ghana phone number.');
        }

        $this->gateway->send($phone, $message);
    }

    /**
     * Converts local Ghana numbers to the 233XXXXXXXXX form used by gateways.
     * Returns null when the number cannot be understood.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '233'.substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            $digits = '233'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '233')) {
            return $digits;
        }

        return null;
    }

    public static function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) < 7) {
            return trim((string) $phone);
        }

        return substr($digits, 0, 3).' ••• '.substr($digits, -4);
    }
}
