<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when an image provider is unavailable or unreachable.
 * 
 * This exception is thrown when the image provider service is down, unreachable,
 * or returns a service unavailable error.
 */
class ImageProviderUnavailableException extends ImageProviderException
{
    /**
     * Create a new image provider unavailable exception instance.
     *
     * @param string|null $providerName The name of the unavailable provider
     * @param string|null $keyword The search keyword that was used
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $providerName = null,
        ?string $keyword = null,
        string $message = "Image provider is currently unavailable",
        int $code = 503,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $providerName, $keyword, $code, $previous);
    }
}
