<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class LlmRateLimitExceededException extends RuntimeException
{
    /**
     * Render the exception.
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
