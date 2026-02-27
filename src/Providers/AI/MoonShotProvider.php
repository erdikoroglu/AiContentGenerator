<?php

declare(strict_types=1);

namespace ErdiKoroglu\AIContentGenerator\Providers\AI;

use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderAuthenticationException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderClientException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderRateLimitException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderTimeoutException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderUnavailableException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MoonShot AI Provider Implementation
 *
 * Provides integration with MoonShot AI's models for content generation.
 * Implements rate limiting, exponential backoff retry logic, comprehensive
 * error handling, and request/response logging.
 *
 * MoonShot AI uses a similar API format to OpenAI, making integration straightforward.
 *
 * Features:
 * - Exponential backoff retry: 1s, 2s, 4s, 8s (max 30s)
 * - Automatic retry on rate limit (429) and 5xx errors
 * - No retry on authentication (401, 403) and client errors (400, 404)
 * - Comprehensive logging with request IDs
 * - Configurable timeout and retry attempts
 *
 * @package ErdiKoroglu\AIContentGenerator\Providers\AI
 */
class MoonShotProvider implements AIProviderInterface
{
    /**
     * MoonShot AI API endpoint for chat completions
     */
    private const API_ENDPOINT = 'https://api.moonshot.cn/v1/chat/completions';

    /**
     * Maximum retry delay in milliseconds (30 seconds)
     */
    private const MAX_RETRY_DELAY = 30000;

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
     * Create a new MoonShot provider instance
     *
     * @param array<string, mixed> $config Provider configuration from config file
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $config['timeout'] ?? 60,
            'headers' => [
                'Authorization' => 'Bearer ' . ($config['api_key'] ?? ''),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function generateContent(string $prompt, array $options = []): string
    {
        $requestId = Str::uuid()->toString();
        $attempt = 0;
        $maxAttempts = $this->config['retry_attempts'] ?? 3;
        $baseDelay = $this->config['retry_delay'] ?? 1000;

        $this->logRequest($requestId, $prompt, $options);

        while ($attempt < $maxAttempts) {
            try {
                $response = $this->makeApiRequest($prompt, $options);
                $content = $this->extractContent($response);

                $this->logSuccess($requestId, $content);

                return $content;
            } catch (ProviderRateLimitException $e) {
                $attempt++;
                
                if ($attempt >= $maxAttempts) {
                    $this->logError($requestId, 'Rate limit exceeded after max attempts', $e);
                    throw $e;
                }

                $delay = $this->calculateRetryDelay($attempt, $baseDelay);
                $this->logRetry($requestId, $attempt, $delay, 'rate limit');
                
                usleep($delay * 1000); // Convert to microseconds
            } catch (ProviderUnavailableException | ProviderTimeoutException $e) {
                $attempt++;
                
                if ($attempt >= $maxAttempts) {
                    $this->logError($requestId, 'Provider unavailable after max attempts', $e);
                    throw $e;
                }

                $delay = $this->calculateRetryDelay($attempt, $baseDelay);
                $this->logRetry($requestId, $attempt, $delay, 'provider unavailable');
                
                usleep($delay * 1000);
            } catch (ProviderAuthenticationException $e) {
                // No retry on authentication errors
                $this->logError($requestId, 'Authentication failed', $e);
                throw $e;
            }
        }

        throw new ProviderUnavailableException(
            $this->getName(),
            'Failed to generate content after ' . $maxAttempts . ' attempts'
        );
    }

    /**
     * Make the actual API request to MoonShot AI
     *
     * @param string $prompt The prompt to send
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed> The API response
     * @throws ProviderAuthenticationException
     * @throws ProviderRateLimitException
     * @throws ProviderTimeoutException
     * @throws ProviderUnavailableException
     */
    private function makeApiRequest(string $prompt, array $options): array
    {
        try {
            $payload = $this->buildPayload($prompt, $options);
            
            $response = $this->client->post(self::API_ENDPOINT, [
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (ServerException $e) {
            $this->handleServerException($e);
        } catch (ConnectException $e) {
            throw new ProviderTimeoutException(
                $this->getName(),
                $this->config['timeout'] ?? 60,
                'Connection to MoonShot AI timed out: ' . $e->getMessage(),
                408,
                $e
            );
        } catch (RequestException $e) {
            throw new ProviderUnavailableException(
                $this->getName(),
                'MoonShot AI request failed: ' . $e->getMessage(),
                503,
                $e
            );
        }
    }

    /**
     * Build the API request payload
     *
     * @param string $prompt The prompt to send
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed>
     */
    private function buildPayload(string $prompt, array $options): array
    {
        return [
            'model' => $options['model'] ?? $this->config['model'] ?? 'moonshot-v1-8k',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
        ];
    }

    /**
     * Extract content from API response
     *
     * @param array<string, mixed> $response The API response
     * @return string The generated content
     * @throws ProviderUnavailableException
     */
    private function extractContent(array $response): string
    {
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new ProviderUnavailableException(
                $this->getName(),
                'Invalid response format from MoonShot AI'
            );
        }

        return trim($response['choices'][0]['message']['content']);
    }

    /**
     * Handle client exceptions (4xx errors)
     *
     * @param ClientException $e The exception
     * @throws ProviderAuthenticationException
     * @throws ProviderRateLimitException
     * @throws ProviderClientException
     */
    private function handleClientException(ClientException $e): void
    {
        $statusCode = $e->getResponse()->getStatusCode();
        $body = $e->getResponse()->getBody()->getContents();

        switch ($statusCode) {
            case 401:
            case 403:
                throw new ProviderAuthenticationException(
                    $this->getName(),
                    'MoonShot AI authentication failed. Please check your API key',
                    $statusCode,
                    $e
                );

            case 429:
                $retryAfter = $this->extractRetryAfter($e);
                throw new ProviderRateLimitException(
                    $this->getName(),
                    $retryAfter,
                    'MoonShot AI rate limit exceeded',
                    429,
                    $e
                );

            case 400:
            case 404:
                // No retry on bad request or not found
                throw new ProviderClientException(
                    $this->getName(),
                    'MoonShot AI request error: ' . $body,
                    $statusCode,
                    $e
                );

            default:
                throw new ProviderClientException(
                    $this->getName(),
                    'MoonShot AI client error: ' . $body,
                    $statusCode,
                    $e
                );
        }
    }

    /**
     * Handle server exceptions (5xx errors)
     *
     * @param ServerException $e The exception
     * @throws ProviderUnavailableException
     */
    private function handleServerException(ServerException $e): void
    {
        $statusCode = $e->getResponse()->getStatusCode();
        $body = $e->getResponse()->getBody()->getContents();

        throw new ProviderUnavailableException(
            $this->getName(),
            'MoonShot AI server error: ' . $body,
            $statusCode,
            $e
        );
    }

    /**
     * Extract retry-after value from rate limit response
     *
     * @param ClientException $e The exception
     * @return int|null Seconds to wait before retry
     */
    private function extractRetryAfter(ClientException $e): ?int
    {
        $response = $e->getResponse();
        
        if ($response->hasHeader('Retry-After')) {
            return (int) $response->getHeader('Retry-After')[0];
        }

        // Try to extract from response body
        $body = json_decode($response->getBody()->getContents(), true);
        if (isset($body['error']['retry_after'])) {
            return (int) $body['error']['retry_after'];
        }

        return null;
    }

    /**
     * Calculate exponential backoff delay
     *
     * @param int $attempt Current attempt number (1-based)
     * @param int $baseDelay Base delay in milliseconds
     * @return int Delay in milliseconds
     */
    private function calculateRetryDelay(int $attempt, int $baseDelay): int
    {
        // Exponential backoff: baseDelay * 2^(attempt-1)
        // Attempt 1: 1s, Attempt 2: 2s, Attempt 3: 4s, Attempt 4: 8s
        $delay = $baseDelay * (2 ** ($attempt - 1));
        
        // Cap at maximum delay (30 seconds)
        return min($delay, self::MAX_RETRY_DELAY);
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
        return 'moonshot';
    }

    /**
     * {@inheritDoc}
     */
    public function validateCredentials(): bool
    {
        if (empty($this->config['api_key'])) {
            Log::warning('MoonShot AI API key is not configured');
            return false;
        }

        try {
            // Make a minimal test request to validate credentials
            $response = $this->client->post(self::API_ENDPOINT, [
                'json' => [
                    'model' => $this->config['model'] ?? 'moonshot-v1-8k',
                    'messages' => [
                        ['role' => 'user', 'content' => 'test'],
                    ],
                    'temperature' => 0.7,
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            
            if ($statusCode === 401 || $statusCode === 403) {
                Log::error('MoonShot AI credentials validation failed', [
                    'status_code' => $statusCode,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }

            // Other client errors might be due to the test request format
            // Consider credentials valid if we get a different error
            return true;
        } catch (\Exception $e) {
            Log::error('MoonShot AI credentials validation error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Log API request
     *
     * @param string $requestId Unique request identifier
     * @param string $prompt The prompt being sent
     * @param array<string, mixed> $options Request options
     */
    private function logRequest(string $requestId, string $prompt, array $options): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::info('MoonShot AI API request initiated', [
            'request_id' => $requestId,
            'provider' => $this->getName(),
            'model' => $options['model'] ?? $this->config['model'] ?? 'moonshot-v1-8k',
            'prompt_length' => strlen($prompt),
            'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
        ]);
    }

    /**
     * Log successful API response
     *
     * @param string $requestId Unique request identifier
     * @param string $content Generated content
     */
    private function logSuccess(string $requestId, string $content): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::info('MoonShot AI API request successful', [
            'request_id' => $requestId,
            'provider' => $this->getName(),
            'content_length' => strlen($content),
        ]);
    }

    /**
     * Log retry attempt
     *
     * @param string $requestId Unique request identifier
     * @param int $attempt Current attempt number
     * @param int $delay Delay before retry in milliseconds
     * @param string $reason Reason for retry
     */
    private function logRetry(string $requestId, int $attempt, int $delay, string $reason): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::warning('MoonShot AI API retry attempt', [
            'request_id' => $requestId,
            'provider' => $this->getName(),
            'attempt' => $attempt,
            'delay_ms' => $delay,
            'reason' => $reason,
        ]);
    }

    /**
     * Log error
     *
     * @param string $requestId Unique request identifier
     * @param string $message Error message
     * @param \Throwable $exception The exception
     */
    private function logError(string $requestId, string $message, \Throwable $exception): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::error('MoonShot AI API request failed', [
            'request_id' => $requestId,
            'provider' => $this->getName(),
            'message' => $message,
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
