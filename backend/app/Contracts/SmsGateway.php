<?php

namespace App\Contracts;

interface SmsGateway
{
    /**
     * Sends an SMS. Implementations should throw on failure so callers can react.
     */
    public function send(string $to, string $message): void;
}
