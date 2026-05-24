<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the TTLock monthly API quota is exhausted. The booking flow
 * catches this and falls back to the locker's permanent PIN instead of
 * registering a timed code on the lock.
 */
class TtlockQuotaExceededException extends RuntimeException
{
    public function __construct(string $message = 'TTLock monthly API quota exceeded.')
    {
        parent::__construct($message);
    }
}
