<?php

namespace App\Exceptions;

use RuntimeException;

class NotAvailableException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly array $availability,
        int $code = 409,
    ) {
        parent::__construct($message, $code);
    }
}
