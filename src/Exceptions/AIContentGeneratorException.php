<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

use RuntimeException;

/**
 * Base exception for all AI Content Generator package exceptions.
 * 
 * All custom exceptions in this package extend from this base exception,
 * allowing consumers to catch all package-specific exceptions with a single catch block.
 */
class AIContentGeneratorException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
