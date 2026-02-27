<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when an invalid or unsupported provider is specified.
 * 
 * This exception is thrown when attempting to use a provider that is not
 * supported by the package or not properly configured.
 */
class InvalidProviderException extends ConfigurationException
{
    /**
     * The invalid provider name.
     *
     * @var string|null
     */
    protected ?string $providerName = null;

    /**
     * The list of supported providers.
     *
     * @var array
     */
    protected array $supportedProviders = [];

    /**
     * Create a new invalid provider exception instance.
     *
     * @param string|null $providerName The invalid provider name
     * @param array $supportedProviders The list of supported providers
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $providerName = null,
        array $supportedProviders = [],
        string $message = "Invalid or unsupported provider",
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        $this->providerName = $providerName;
        $this->supportedProviders = $supportedProviders;
        
        if ($providerName) {
            $message .= ": {$providerName}";
        }
        
        if (!empty($supportedProviders)) {
            $message .= ". Supported providers: " . implode(', ', $supportedProviders);
        }
        
        parent::__construct($message, 'default_ai_provider', $code, $previous);
    }

    /**
     * Get the invalid provider name.
     *
     * @return string|null
     */
    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    /**
     * Get the list of supported providers.
     *
     * @return array
     */
    public function getSupportedProviders(): array
    {
        return $this->supportedProviders;
    }
}
