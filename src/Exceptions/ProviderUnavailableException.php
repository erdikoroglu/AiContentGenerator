<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when an AI provider is unavailable or unreachable.
 * 
 * This exception is thrown when the provider service is down, unreachable,
 * or returns a service unavailable error (e.g., 503 status code).
 */
class ProviderUnavailableException extends AIProviderException
{
    /**
     * Create a new provider unavailable exception instance.
     *
     * @param string|null $providerName The name of the unavailable provider
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $providerName = null,
        string $message = "AI provider is currently unavailable",
        int $code = 503,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $providerName, $code, $previous);
    }
}
