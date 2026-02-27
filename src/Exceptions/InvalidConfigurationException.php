<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when configuration values are invalid.
 * 
 * This exception is thrown when configuration values do not meet
 * validation requirements (e.g., invalid timeout values, invalid cache TTL, etc.).
 */
class InvalidConfigurationException extends ConfigurationException
{
    /**
     * The invalid configuration value.
     *
     * @var mixed
     */
    protected mixed $invalidValue = null;

    /**
     * The expected value type or format.
     *
     * @var string|null
     */
    protected ?string $expectedFormat = null;

    /**
     * Create a new invalid configuration exception instance.
     *
     * @param string|null $configKey The configuration key with invalid value
     * @param mixed $invalidValue The invalid configuration value
     * @param string|null $expectedFormat The expected value type or format
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $configKey = null,
        mixed $invalidValue = null,
        ?string $expectedFormat = null,
        string $message = "Invalid configuration value",
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        $this->invalidValue = $invalidValue;
        $this->expectedFormat = $expectedFormat;
        
        if ($invalidValue !== null) {
            $valueStr = is_scalar($invalidValue) ? (string) $invalidValue : gettype($invalidValue);
            $message .= ": {$valueStr}";
        }
        
        if ($expectedFormat) {
            $message .= ". Expected: {$expectedFormat}";
        }
        
        parent::__construct($message, $configKey, $code, $previous);
    }

    /**
     * Get the invalid configuration value.
     *
     * @return mixed
     */
    public function getInvalidValue(): mixed
    {
        return $this->invalidValue;
    }

    /**
     * Get the expected value type or format.
     *
     * @return string|null
     */
    public function getExpectedFormat(): ?string
    {
        return $this->expectedFormat;
    }
}
