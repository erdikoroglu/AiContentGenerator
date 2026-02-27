<?php

declare(strict_types=1);

namespace ErdiKoroglu\AIContentGenerator\Providers\Image;

use ErdiKoroglu\AIContentGenerator\DTOs\ImageResult;

/**
 * Image Provider Interface
 *
 * Defines the contract that all image search providers must implement.
 * This interface enables the Strategy pattern, allowing runtime selection and
 * switching between different image providers (Pexels, Pixabay, etc.).
 *
 * Implementations should handle:
 * - API client configuration and authentication
 * - Image search and relevance scoring
 * - Rate limiting and quota management
 * - Error handling and logging
 * - Response transformation to ImageResult objects
 *
 * @package ErdiKoroglu\AIContentGenerator\Providers\Image
 */
interface ImageProviderInterface
{
    /**
     * Search for images based on a keyword
     *
     * Queries the image provider's API to find relevant images matching the given keyword.
     * The method should handle API communication, error handling, rate limiting internally.
     *
     * Results are returned as an array of ImageResult objects, each containing:
     * - url: Direct URL to the image
     * - altText: Suggested alt text for accessibility
     * - attribution: Attribution information (if required by provider)
     * - relevanceScore: Relevance score from 0.0 to 1.0 (higher is more relevant)
     * - width: Image width in pixels
     * - height: Image height in pixels
     *
     * Images should be sorted by relevance score in descending order.
     *
     * @param string $keyword The search keyword or phrase to find relevant images
     * @param int $limit Maximum number of images to return (default: 5)
     *
     * @return array<int, ImageResult> Array of ImageResult objects sorted by relevance
     *
     * @throws \ErdiKoroglu\AIContentGenerator\Exceptions\ImageProviderException
     *         When the provider fails to search for images
     * @throws \ErdiKoroglu\AIContentGenerator\Exceptions\ImageProviderUnavailableException
     *         When the provider service is unavailable
     */
    public function searchImages(string $keyword, int $limit = 5): array;

    /**
     * Check if the image provider is currently available
     *
     * Determines whether the provider can be used for image searches.
     * This should verify:
     * - API credentials are configured
     * - The provider service is reachable
     * - No critical errors or maintenance mode
     * - Rate limits are not exceeded
     *
     * This method should be lightweight and not make actual API calls if possible.
     * It's used to determine whether to attempt image searches or gracefully skip them.
     *
     * @return bool True if the provider is available and ready to use, false otherwise
     */
    public function isAvailable(): bool;

    /**
     * Get the provider's name
     *
     * Returns a unique identifier for this provider. This name is used for:
     * - Configuration lookups
     * - Logging and debugging
     * - Provider selection in ContentGeneratorService
     * - Cache key generation
     *
     * The name should be lowercase and match the configuration key.
     * Examples: 'pexels', 'pixabay'
     *
     * @return string The provider's unique identifier name
     */
    public function getName(): string;
}
