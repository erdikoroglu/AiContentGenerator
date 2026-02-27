<?php

declare(strict_types=1);

namespace ErdiKoroglu\AIContentGenerator\Providers\Image;

use ErdiKoroglu\AIContentGenerator\DTOs\ImageResult;

/**
 * Mock Image Provider
 *
 * A mock implementation of ImageProviderInterface for testing purposes.
 * This provider returns configurable mock images without making actual API calls,
 * enabling fast, predictable tests and development without API credentials.
 *
 * Features:
 * - Configurable mock image results
 * - Support for different scenarios (success, empty results, error)
 * - No actual API calls
 * - Configurable via constructor or config
 *
 * @package ErdiKoroglu\AIContentGenerator\Providers\Image
 */
class MockImageProvider implements ImageProviderInterface
{
    /**
     * @var array<int, ImageResult> Mock image results
     */
    private array $mockImages;

    /**
     * @var bool Whether the provider should simulate being available
     */
    private bool $available;

    /**
     * @var string|null Exception class to throw (for error scenarios)
     */
    private ?string $exceptionToThrow;

    /**
     * Create a new MockImageProvider instance
     *
     * @param array<int, ImageResult>|null $mockImages Custom mock images (uses defaults if null)
     * @param bool $available Whether provider should be available
     * @param string|null $exceptionToThrow Exception class name to throw on searchImages
     */
    public function __construct(
        ?array $mockImages = null,
        bool $available = true,
        ?string $exceptionToThrow = null
    ) {
        $this->mockImages = $mockImages ?? $this->getDefaultMockImages();
        $this->available = $available;
        $this->exceptionToThrow = $exceptionToThrow;
    }

    /**
     * Search for mock images
     *
     * Returns the configured mock images or throws an exception if configured to do so.
     *
     * @param string $keyword The search keyword (used to generate relevant alt text)
     * @param int $limit Maximum number of images to return
     *
     * @return array<int, ImageResult> Array of mock ImageResult objects
     *
     * @throws \Exception If configured to throw an exception
     */
    public function searchImages(string $keyword, int $limit = 5): array
    {
        if ($this->exceptionToThrow !== null) {
            throw new $this->exceptionToThrow('Mock image provider configured to throw exception');
        }

        // Limit the results to the requested amount
        $results = array_slice($this->mockImages, 0, $limit);

        // Update alt text to include the keyword for more realistic mocking
        return array_map(function (ImageResult $image) use ($keyword) {
            return new ImageResult(
                url: $image->url,
                altText: ucfirst($keyword) . ' - ' . $image->altText,
                attribution: $image->attribution,
                relevanceScore: $image->relevanceScore,
                width: $image->width,
                height: $image->height
            );
        }, $results);
    }

    /**
     * Check if the mock provider is available
     *
     * @return bool The configured availability status
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Get the provider's name
     *
     * @return string Always returns 'mock'
     */
    public function getName(): string
    {
        return 'mock';
    }

    /**
     * Set custom mock images
     *
     * @param array<int, ImageResult> $images The mock images to return
     * @return self
     */
    public function setMockImages(array $images): self
    {
        $this->mockImages = $images;
        return $this;
    }

    /**
     * Set availability status
     *
     * @param bool $available Whether provider should be available
     * @return self
     */
    public function setAvailable(bool $available): self
    {
        $this->available = $available;
        return $this;
    }

    /**
     * Configure exception to throw
     *
     * @param string|null $exceptionClass Exception class name to throw
     * @return self
     */
    public function setExceptionToThrow(?string $exceptionClass): self
    {
        $this->exceptionToThrow = $exceptionClass;
        return $this;
    }

    /**
     * Get default mock images
     *
     * Returns a set of realistic mock image results for testing.
     *
     * @return array<int, ImageResult> Default mock images
     */
    private function getDefaultMockImages(): array
    {
        return [
            new ImageResult(
                url: 'https://images.example.com/mock-image-1.jpg',
                altText: 'Professional workspace with laptop',
                attribution: 'Mock Image Provider',
                relevanceScore: 0.95,
                width: 1920,
                height: 1080
            ),
            new ImageResult(
                url: 'https://images.example.com/mock-image-2.jpg',
                altText: 'Modern office environment',
                attribution: 'Mock Image Provider',
                relevanceScore: 0.88,
                width: 1600,
                height: 900
            ),
            new ImageResult(
                url: 'https://images.example.com/mock-image-3.jpg',
                altText: 'Technology and innovation concept',
                attribution: 'Mock Image Provider',
                relevanceScore: 0.82,
                width: 1920,
                height: 1280
            ),
            new ImageResult(
                url: 'https://images.example.com/mock-image-4.jpg',
                altText: 'Team collaboration scene',
                attribution: 'Mock Image Provider',
                relevanceScore: 0.75,
                width: 1280,
                height: 720
            ),
            new ImageResult(
                url: 'https://images.example.com/mock-image-5.jpg',
                altText: 'Digital workspace setup',
                attribution: 'Mock Image Provider',
                relevanceScore: 0.70,
                width: 1600,
                height: 1200
            ),
        ];
    }
}
