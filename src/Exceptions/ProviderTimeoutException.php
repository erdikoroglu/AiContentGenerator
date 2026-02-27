<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when an AI provider request times out.
 * 
 * This exception is thrown when the provider does not respond within
 * the configured timeout period.
 */
class ProviderTimeoutException extends AIProviderException
{
    /**
     * The timeout duration in seconds.
     *
     * @var int|null
     */
    protected ?int $timeout = null;

    /**
     * Create a new provider timeout exception instance.
     *
     * @param string|null $providerName The name of the provider that timed out
     * @param int|null $timeout The timeout duration in seconds
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $providerName = null,
        ?int $timeout = null,
        string $message = "AI provider request timed out",
        int $code = 408,
        ?\Throwable $previous = null
    ) {
        $this->timeout = $timeout;
        
        if ($timeout) {
            $message .= " after {$timeout} seconds";
        }
        
        parent::__construct($message, $providerName, $code, $previous);
    }

    /**
     * Get the timeout duration in seconds.
     *
     * @return int|null
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
    }
}
