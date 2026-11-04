<?php

namespace App\Exceptions;

use RuntimeException;

class PassChargeException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 422,
        public readonly ?string $errorCode = null,
        public readonly array $context = []
    ) {
        parent::__construct($message);
    }

    public static function suspended(): self
    {
        return new self('This pass is suspended. Ask an administrator to reactivate it.');
    }

    public static function noFare(): self
    {
        return new self('This route has no active fare. Ask an administrator to set one.');
    }

    public static function alreadyCharged($ride): self
    {
        return new self(
            'This pass was just charged. Wait a moment before charging again.',
            409,
            'pass_already_charged',
            ['ride' => $ride]
        );
    }
}
