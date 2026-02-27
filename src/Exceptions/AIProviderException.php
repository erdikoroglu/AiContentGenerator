<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when AI provider operations fail.
 * 
 * This is the base exception for all AI provider-related errors,
 * including authentication, availability, rate limiting, and timeout issues.
 */
class AIProviderException extends AIContentGeneratorException
{
    /**
     * The name of the AI provider that caused the exception.
     *
     * @var string|null
     */
    protected ?string $providerName = null;

    /**
     * Create a new AI provider exception instance.
     *
     * @param string $message The exception message
     * @param string|null $providerName The name of the provider that failed
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        string $message = "",
        ?string $providerName = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->providerName = $providerName;
        
        if ($providerName) {
            $message = "[{$providerName}] {$message}";
        }
        
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the provider name that caused the exception.
     *
     * @return string|null
     */
    public function getProviderName(): ?string
    {
        return $this->providerName;
    }
}
