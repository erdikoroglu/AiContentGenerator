<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when package configuration is invalid.
 * 
 * This is the base exception for all configuration-related errors,
 * including invalid provider selection, missing credentials, and invalid configuration values.
 */
class ConfigurationException extends AIContentGeneratorException
{
    /**
     * The configuration key that caused the exception.
     *
     * @var string|null
     */
    protected ?string $configKey = null;

    /**
     * Create a new configuration exception instance.
     *
     * @param string $message The exception message
     * @param string|null $configKey The configuration key that caused the exception
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        string $message = "Configuration error",
        ?string $configKey = null,
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        $this->configKey = $configKey;
        
        if ($configKey) {
            $message = "[{$configKey}] {$message}";
        }
        
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the configuration key that caused the exception.
     *
     * @return string|null
     */
    public function getConfigKey(): ?string
    {
        return $this->configKey;
    }
}
