<?php

declare(strict_types=1);

namespace ErdiKoroglu\AIContentGenerator\Providers\AI;

/**
 * AI Provider Interface
 *
 * Defines the contract that all AI content generation providers must implement.
 * This interface enables the Strategy pattern, allowing runtime selection and
 * switching between different AI providers (OpenAI, MoonShot AI, Google AI, etc.).
 *
 * Implementations should handle:
 * - API client configuration and authentication
 * - Rate limiting and quota management
 * - Retry logic with exponential backoff
 * - Error handling and logging
 * - Request/response transformation
 *
 * @package ErdiKoroglu\AIContentGenerator\Providers\AI
 */
interface AIProviderInterface
{
    /**
     * Generate content using the AI provider
     *
     * Sends a prompt to the AI provider and returns the generated content as a string.
     * The method should handle API communication, error handling, rate limiting,
     * and retry logic internally.
     *
     * The prompt typically includes:
     * - System instructions (content type, expertise, E-E-A-T guidelines)
     * - User requirements (topic, keywords, word count, locale)
     * - Output format specifications (HTML structure, JSON format)
     *
     * @param string $prompt The complete prompt to send to the AI provider
     * @param array<string, mixed> $options Optional provider-specific configuration options
     *                                      May include: temperature, max_tokens, model, etc.
     *
     * @return string The generated content from the AI provider
     *
     * @throws \ErdiKoroglu\AIContentGenerator\Exceptions\AIProviderException
     *         When the provider fails to generate content
     * @throws \ErdiKoroglu\AIContentGenerator\Exceptions\ProviderAuthenticationException
     *         When authentication with the provider fails
     * @throws \ErdiKoroglu\AIContentGenerator\Exceptions\ProviderRateLimitException
     *         When the provider's rate limit is exceeded
     * @throws \ErdiKoroglu\AIContentGenerator\Exceptions\ProviderTimeoutException
     *         When the provider request times out
     */
    public function generateContent(string $prompt, array $options = []): string;

    /**
     * Check if the AI provider is currently available
     *
     * Determines whether the provider can be used for content generation.
     * This should verify:
     * - API credentials are configured
     * - The provider service is reachable
     * - No critical errors or maintenance mode
     *
     * This method should be lightweight and not make actual API calls if possible.
     * It's used by the fallback mechanism to determine which providers to try.
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
     * - Fallback chain management
     *
     * The name should be lowercase and match the configuration key.
     * Examples: 'openai', 'moonshot', 'google'
     *
     * @return string The provider's unique identifier name
     */
    public function getName(): string;

    /**
     * Validate the provider's API credentials
     *
     * Performs validation of the configured API credentials by making a test
     * request to the provider's API. This is typically called during:
     * - Application startup/configuration
     * - Provider initialization
     * - Health checks
     *
     * This method should:
     * - Verify API key format and validity
     * - Make a lightweight test API call if necessary
     * - Return false instead of throwing exceptions when possible
     * - Log validation failures for debugging
     *
     * @return bool True if credentials are valid and working, false otherwise
     */
    public function validateCredentials(): bool;
}
