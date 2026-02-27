<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when an AI provider returns a client error (4xx) that should not be retried.
 * 
 * This exception is thrown for errors like 400 (Bad Request) or 404 (Not Found)
 * where retrying the request would not help.
 */
class ProviderClientException extends AIProviderException
{
    /**
     * Create a new provider client exception instance.
     *
     * @param string|null $providerName The name of the provider that returned the error
     * @param string $message The exception message
     * @param int $code The HTTP status code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $providerName = null,
        string $message = "AI provider client error",
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $providerName, $code, $previous);
    }
}
