<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when required API credentials are missing.
 * 
 * This exception is thrown when attempting to use a provider without
 * configuring the required API key or credentials.
 */
class MissingCredentialsException extends ConfigurationException
{
    /**
     * The provider name that is missing credentials.
     *
     * @var string|null
     */
    protected ?string $providerName = null;

    /**
     * Create a new missing credentials exception instance.
     *
     * @param string|null $providerName The provider name that is missing credentials
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $providerName = null,
        string $message = "API credentials are missing",
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        $this->providerName = $providerName;
        
        if ($providerName) {
            $message .= " for provider: {$providerName}";
        }
        
        $configKey = $providerName ? "ai_providers.{$providerName}.api_key" : null;
        
        parent::__construct($message, $configKey, $code, $previous);
    }

    /**
     * Get the provider name that is missing credentials.
     *
     * @return string|null
     */
    public function getProviderName(): ?string
    {
        return $this->providerName;
    }
}
