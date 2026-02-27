<?php

declare(strict_types=1);

namespace ErdiKoroglu\AIContentGenerator\Providers\AI;

/**
 * Mock AI Provider
 *
 * A mock implementation of AIProviderInterface for testing purposes.
 * This provider returns configurable mock responses without making actual API calls,
 * enabling fast, predictable tests and development without API credentials.
 *
 * Features:
 * - Configurable mock responses
 * - Support for different scenarios (success, error, timeout)
 * - No actual API calls
 * - Configurable via constructor or config
 *
 * @package ErdiKoroglu\AIContentGenerator\Providers\AI
 */
class MockAIProvider implements AIProviderInterface
{
    /**
     * @var string Default mock content response
     */
    private string $mockContent;

    /**
     * @var bool Whether the provider should simulate being available
     */
    private bool $available;

    /**
     * @var bool Whether credentials should validate successfully
     */
    private bool $credentialsValid;

    /**
     * @var string|null Exception class to throw (for error scenarios)
     */
    private ?string $exceptionToThrow;

    /**
     * Create a new MockAIProvider instance
     *
     * @param string|null $mockContent Custom mock content (uses default if null)
     * @param bool $available Whether provider should be available
     * @param bool $credentialsValid Whether credentials should validate
     * @param string|null $exceptionToThrow Exception class name to throw on generateContent
     */
    public function __construct(
        ?string $mockContent = null,
        bool $available = true,
        bool $credentialsValid = true,
        ?string $exceptionToThrow = null
    ) {
        $this->mockContent = $mockContent ?? $this->getDefaultMockContent();
        $this->available = $available;
        $this->credentialsValid = $credentialsValid;
        $this->exceptionToThrow = $exceptionToThrow;
    }

    /**
     * Generate mock content
     *
     * Returns the configured mock content or throws an exception if configured to do so.
     *
     * @param string $prompt The prompt (ignored in mock)
     * @param array<string, mixed> $options Options (ignored in mock)
     *
     * @return string The mock content
     *
     * @throws \Exception If configured to throw an exception
     */
    public function generateContent(string $prompt, array $options = []): string
    {
        if ($this->exceptionToThrow !== null) {
            throw new $this->exceptionToThrow('Mock provider configured to throw exception');
        }

        return $this->mockContent;
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
     * Validate mock credentials
     *
     * @return bool The configured credential validation status
     */
    public function validateCredentials(): bool
    {
        return $this->credentialsValid;
    }

    /**
     * Set custom mock content
     *
     * @param string $content The mock content to return
     * @return self
     */
    public function setMockContent(string $content): self
    {
        $this->mockContent = $content;
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
     * Set credentials validation status
     *
     * @param bool $valid Whether credentials should validate
     * @return self
     */
    public function setCredentialsValid(bool $valid): self
    {
        $this->credentialsValid = $valid;
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
     * Get default mock content
     *
     * Returns a realistic mock content response with proper HTML structure,
     * FAQs, and all required elements for testing.
     *
     * @return string Default mock content
     */
    private function getDefaultMockContent(): string
    {
        return json_encode([
            'title' => 'Mock Article Title About Laravel Development',
            'meta_description' => 'This is a mock meta description for testing purposes. It contains exactly 160 characters to meet SEO requirements for optimal display.',
            'excerpt' => 'This is a mock excerpt for testing. It provides a brief summary of the article content and should be between 100-150 words. Laravel is a powerful PHP framework that makes web development easier and more enjoyable. This mock content demonstrates the structure and format expected from the AI content generator. It includes all necessary elements for proper testing of the content generation system.',
            'content' => '<h2>Introduction to Mock Content</h2><p>This is the introduction paragraph of our mock content. It provides context and sets up the main topic. Laravel development is an important skill for modern web developers.</p><h2>Main Content Section</h2><p>This is the main content section with detailed information. It contains multiple paragraphs and covers the topic comprehensively.</p><p>Additional paragraph with more details about the topic. This ensures we meet word count requirements.</p><h3>Subsection Details</h3><p>This subsection provides more specific information about a particular aspect of the topic.</p><h2>Practical Examples</h2><p>Here we provide practical examples and use cases. This section demonstrates how to apply the concepts discussed.</p><p>More detailed examples and explanations to ensure comprehensive coverage of the topic.</p><h2>Conclusion</h2><p>This is the conclusion paragraph that summarizes the key points and provides final thoughts. For more information, please <a href="https://example.com/contact" target="_blank" rel="nofollow">contact us</a>.</p>',
            'faqs' => [
                [
                    'question' => 'What is Laravel?',
                    'answer' => 'Laravel is a modern PHP framework for web application development.'
                ],
                [
                    'question' => 'Why use Laravel?',
                    'answer' => 'Laravel provides elegant syntax, powerful tools, and a great developer experience.'
                ],
                [
                    'question' => 'How to get started with Laravel?',
                    'answer' => 'You can start by installing Laravel via Composer and following the official documentation.'
                ]
            ]
        ]);
    }
}
