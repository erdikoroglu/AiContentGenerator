<?php

declare(strict_types=1);

namespace ErdiKoroglu\AIContentGenerator\Providers\Image;

use ErdiKoroglu\AIContentGenerator\DTOs\ImageResult;
use ErdiKoroglu\AIContentGenerator\Exceptions\ImageProviderUnavailableException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ImageSearchException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pexels Provider Implementation
 *
 * Provides integration with Pexels API for image search.
 * Implements relevance scoring, rate limiting handling, and comprehensive
 * error handling with logging.
 *
 * Features:
 * - Image search with keyword matching
 * - Relevance scoring based on alt text similarity
 * - Automatic rate limiting detection (403, 429)
 * - Graceful error handling - returns empty array on failure
 * - Comprehensive logging with request IDs
 *
 * @package ErdiKoroglu\AIContentGenerator\Providers\Image
 */
class PexelsProvider implements ImageProviderInterface
{
    /**
     * Pexels API endpoint for image search
     */
    private const API_ENDPOINT = 'https://api.pexels.com/v1/search';

    /**
     * HTTP client for API requests
     *
     * @var Client
     */
    private Client $client;

    /**
     * Provider configuration
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Create a new Pexels provider instance
     *
     * @param array<string, mixed> $config Provider configuration from config file
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'headers' => [
                'Authorization' => $config['api_key'] ?? '',
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function searchImages(string $keyword, int $limit = 5): array
    {
        $requestId = Str::uuid()->toString();

        try {
            $this->logRequest($requestId, $keyword, $limit);

            $response = $this->makeApiRequest($keyword, $limit);
            $images = $this->transformResponse($response, $keyword);

            $this->logSuccess($requestId, count($images));

            return $images;
        } catch (ImageProviderUnavailableException | ImageSearchException $e) {
            // Graceful failure - log error and return empty array
            $this->logError($requestId, $keyword, $e);
            return [];
        }
    }

    /**
     * Make the actual API request to Pexels
     *
     * @param string $keyword The search keyword
     * @param int $limit Maximum number of images to return
     * @return array<string, mixed> The API response
     * @throws ImageProviderUnavailableException
     * @throws ImageSearchException
     */
    private function makeApiRequest(string $keyword, int $limit): array
    {
        try {
            $response = $this->client->get(self::API_ENDPOINT, [
                'query' => [
                    'query' => $keyword,
                    'per_page' => $limit,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $this->handleClientException($e, $keyword);
        } catch (ServerException $e) {
            throw new ImageProviderUnavailableException(
                $this->getName(),
                $keyword,
                'Pexels server error: ' . $e->getMessage(),
                $e->getResponse()->getStatusCode(),
                $e
            );
        } catch (ConnectException $e) {
            throw new ImageProviderUnavailableException(
                $this->getName(),
                $keyword,
                'Connection to Pexels timed out: ' . $e->getMessage(),
                408,
                $e
            );
        } catch (RequestException $e) {
            throw new ImageSearchException(
                $this->getName(),
                $keyword,
                'Pexels request failed: ' . $e->getMessage(),
                503,
                $e
            );
        }
    }

    /**
     * Handle client exceptions (4xx errors)
     *
     * @param ClientException $e The exception
     * @param string $keyword The search keyword
     * @throws ImageProviderUnavailableException
     * @throws ImageSearchException
     */
    private function handleClientException(ClientException $e, string $keyword): void
    {
        $statusCode = $e->getResponse()->getStatusCode();
        $body = $e->getResponse()->getBody()->getContents();

        // Rate limiting errors (403 or 429)
        if ($statusCode === 403 || $statusCode === 429) {
            throw new ImageProviderUnavailableException(
                $this->getName(),
                $keyword,
                'Pexels rate limit exceeded',
                $statusCode,
                $e
            );
        }

        // Other client errors
        throw new ImageSearchException(
            $this->getName(),
            $keyword,
            'Pexels client error: ' . $body,
            $statusCode,
            $e
        );
    }

    /**
     * Transform API response to ImageResult objects
     *
     * @param array<string, mixed> $response The API response
     * @param string $keyword The search keyword for relevance scoring
     * @return array<int, ImageResult> Array of ImageResult objects sorted by relevance
     */
    private function transformResponse(array $response, string $keyword): array
    {
        if (!isset($response['photos']) || !is_array($response['photos'])) {
            return [];
        }

        $images = [];

        foreach ($response['photos'] as $photo) {
            if (!$this->isValidPhoto($photo)) {
                continue;
            }

            $altText = $photo['alt'] ?? '';
            $relevanceScore = $this->calculateRelevanceScore($altText, $keyword);

            $images[] = new ImageResult(
                url: $photo['src']['large'] ?? $photo['src']['original'] ?? '',
                altText: $altText,
                attribution: $this->buildAttribution($photo),
                relevanceScore: $relevanceScore,
                width: (int) ($photo['width'] ?? 0),
                height: (int) ($photo['height'] ?? 0)
            );
        }

        // Sort by relevance score in descending order
        usort($images, function (ImageResult $a, ImageResult $b) {
            return $b->relevanceScore <=> $a->relevanceScore;
        });

        return $images;
    }

    /**
     * Validate photo data structure
     *
     * @param mixed $photo The photo data
     * @return bool True if valid, false otherwise
     */
    private function isValidPhoto($photo): bool
    {
        return is_array($photo)
            && isset($photo['src'])
            && is_array($photo['src'])
            && (isset($photo['src']['large']) || isset($photo['src']['original']));
    }

    /**
     * Calculate relevance score based on alt text similarity to keyword
     *
     * Uses a simple word matching algorithm:
     * - Exact match: 1.0
     * - Contains all keyword words: 0.8
     * - Contains some keyword words: 0.4 to 0.7 (proportional)
     * - No match: 0.2 (default)
     *
     * @param string $altText The image alt text
     * @param string $keyword The search keyword
     * @return float Relevance score between 0.0 and 1.0
     */
    private function calculateRelevanceScore(string $altText, string $keyword): float
    {
        $altText = strtolower(trim($altText));
        $keyword = strtolower(trim($keyword));

        // Exact match
        if ($altText === $keyword) {
            return 1.0;
        }

        // Contains exact keyword phrase
        if (str_contains($altText, $keyword)) {
            return 0.9;
        }

        // Word-based matching
        $keywordWords = array_filter(explode(' ', $keyword));
        $altWords = array_filter(explode(' ', $altText));

        if (empty($keywordWords) || empty($altWords)) {
            return 0.2;
        }

        // Count matching words
        $matchCount = 0;
        foreach ($keywordWords as $keywordWord) {
            foreach ($altWords as $altWord) {
                if ($keywordWord === $altWord || str_contains($altWord, $keywordWord)) {
                    $matchCount++;
                    break;
                }
            }
        }

        // Calculate score based on match percentage
        $matchPercentage = $matchCount / count($keywordWords);

        if ($matchPercentage >= 1.0) {
            return 0.8; // All keyword words found
        } elseif ($matchPercentage >= 0.5) {
            return 0.4 + ($matchPercentage * 0.4); // 0.6 to 0.8
        } elseif ($matchPercentage > 0) {
            return 0.2 + ($matchPercentage * 0.4); // 0.2 to 0.6
        }

        return 0.2; // Default score
    }

    /**
     * Build attribution string for the image
     *
     * @param array<string, mixed> $photo The photo data
     * @return string|null Attribution string or null
     */
    private function buildAttribution(array $photo): ?string
    {
        $photographer = $photo['photographer'] ?? null;
        $photographerUrl = $photo['photographer_url'] ?? null;

        if (!$photographer) {
            return null;
        }

        if ($photographerUrl) {
            return "Photo by {$photographer} on Pexels ({$photographerUrl})";
        }

        return "Photo by {$photographer} on Pexels";
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        // Check if API key is configured
        if (empty($this->config['api_key'])) {
            return false;
        }

        // Provider is considered available if credentials are configured
        // Actual availability is checked during API calls
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'pexels';
    }

    /**
     * Log API request
     *
     * @param string $requestId Unique request identifier
     * @param string $keyword The search keyword
     * @param int $limit Maximum number of images
     */
    private function logRequest(string $requestId, string $keyword, int $limit): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::info('Pexels API request initiated', [
            'request_id' => $requestId,
            'provider' => $this->getName(),
            'keyword' => $keyword,
            'limit' => $limit,
        ]);
    }

    /**
     * Log successful API response
     *
     * @param string $requestId Unique request identifier
     * @param int $imageCount Number of images returned
     */
    private function logSuccess(string $requestId, int $imageCount): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::info('Pexels API request successful', [
            'request_id' => $requestId,
            'provider' => $this->getName(),
            'image_count' => $imageCount,
        ]);
    }

    /**
     * Log error
     *
     * @param string $requestId Unique request identifier
     * @param string $keyword The search keyword
     * @param \Throwable $exception The exception
     */
    private function logError(string $requestId, string $keyword, \Throwable $exception): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::warning('Pexels API request failed - returning empty array', [
            'request_id' => $requestId,
            'provider' => $this->getName(),
            'keyword' => $keyword,
            'exception' => get_class($exception),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    private function isLoggingEnabled(): bool
    {
        return config('ai-content-generator.logging.enabled', true);
    }
}
