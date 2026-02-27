<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when AI provider rate limit is exceeded.
 * 
 * This exception is thrown when the provider returns a rate limit error (e.g., 429 status code).
 * The exception includes retry-after information when available.
 */
class ProviderRateLimitException extends AIProviderException
{
    /**
     * The number of seconds to wait before retrying.
     *
     * @var int|null
     */
    protected ?int $retryAfter = null;

    /**
     * Create a new provider rate limit exception instance.
     *
     * @param string|null $providerName The name of the provider that rate limited the request
     * @param int|null $retryAfter The number of seconds to wait before retrying
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $providerName = null,
        ?int $retryAfter = null,
        string $message = "AI provider rate limit exceeded",
        int $code = 429,
        ?\Throwable $previous = null
    ) {
        $this->retryAfter = $retryAfter;
        
        if ($retryAfter) {
            $message .= ". Retry after {$retryAfter} seconds";
        }
        
        parent::__construct($message, $providerName, $code, $previous);
    }

    /**
     * Get the number of seconds to wait before retrying.
     *
     * @return int|null
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
