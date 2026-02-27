<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when image provider operations fail.
 * 
 * This is the base exception for all image provider-related errors,
 * including search failures and provider unavailability.
 */
class ImageProviderException extends AIContentGeneratorException
{
    /**
     * The name of the image provider that caused the exception.
     *
     * @var string|null
     */
    protected ?string $providerName = null;

    /**
     * The search keyword that was used.
     *
     * @var string|null
     */
    protected ?string $keyword = null;

    /**
     * Create a new image provider exception instance.
     *
     * @param string $message The exception message
     * @param string|null $providerName The name of the provider that failed
     * @param string|null $keyword The search keyword that was used
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        string $message = "Image provider operation failed",
        ?string $providerName = null,
        ?string $keyword = null,
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        $this->providerName = $providerName;
        $this->keyword = $keyword;
        
        if ($providerName) {
            $message = "[{$providerName}] {$message}";
        }
        
        if ($keyword) {
            $message .= " for keyword: {$keyword}";
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

    /**
     * Get the search keyword that was used.
     *
     * @return string|null
     */
    public function getKeyword(): ?string
    {
        return $this->keyword;
    }
}
