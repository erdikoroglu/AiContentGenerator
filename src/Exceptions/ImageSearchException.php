<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when image search operation fails.
 * 
 * This exception is thrown when the image provider fails to search for images,
 * returns an error, or the search operation times out.
 */
class ImageSearchException extends ImageProviderException
{
    /**
     * Create a new image search exception instance.
     *
     * @param string|null $providerName The name of the provider that failed
     * @param string|null $keyword The search keyword that was used
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $providerName = null,
        ?string $keyword = null,
        string $message = "Image search failed",
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $providerName, $keyword, $code, $previous);
    }
}
