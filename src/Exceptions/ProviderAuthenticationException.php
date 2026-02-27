<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when AI provider authentication fails.
 * 
 * This exception is thrown when API credentials are invalid, expired,
 * or when the provider returns an authentication error (e.g., 401, 403 status codes).
 */
class ProviderAuthenticationException extends AIProviderException
{
    /**
     * Create a new provider authentication exception instance.
     *
     * @param string|null $providerName The name of the provider with authentication issues
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $providerName = null,
        string $message = "AI provider authentication failed. Please check your API credentials",
        int $code = 401,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $providerName, $code, $previous);
    }
}
