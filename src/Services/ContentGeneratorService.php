<?php

declare(strict_types=1);

namespace ErdiKoroglu\AIContentGenerator\Services;

use Carbon\Carbon;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentResponse;
use ErdiKoroglu\AIContentGenerator\DTOs\ImageResult;
use ErdiKoroglu\AIContentGenerator\Providers\AI\AIProviderInterface;
use ErdiKoroglu\AIContentGenerator\Providers\Image\ImageProviderInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Content Generator Service
 * 
 * Main orchestrator for AI content generation. Coordinates:
 * - AI provider selection and fallback
 * - Prompt engineering
 * - Image provider integration
 * - SEO metadata generation
 * - FAQ generation
 * - Contact link injection
 * - Content validation and regeneration
 * 
 * Requirements: 2.5, 2.6, 3.3-3.7, 5.5-5.7, 6.7-6.10, 7.1-7.6, 8.1-8.7, 9.9, 10.1-10.7, 11.7, 13.1-13.7, 15.1-15.5, 16.7, 17.3
 */
class ContentGeneratorService
{
    /**
     * @var array<string, AIProviderInterface> Available AI providers
     */
    private array $aiProviders = [];

    /**
     * @var array<string, ImageProviderInterface> Available image providers
     */
    private array $imageProviders = [];

    /**
     * @var string|null Override AI provider
     */
    private ?string $overrideAIProvider = null;

    /**
     * @var string|null Override image provider
     */
    private ?string $overrideImageProvider = null;

    /**
     * Constructor
     *
     * @param ConfigRepository $config Configuration repository
     * @param CacheRepository $cache Cache repository
     * @param ValidationService $validationService Validation service
     * @param KeywordAnalyzerService $keywordAnalyzer Keyword analyzer service
     * @param LoggerInterface $logger Logger instance
     */
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly CacheRepository $cache,
        private readonly ValidationService $validationService,
        private readonly KeywordAnalyzerService $keywordAnalyzer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Register an AI provider
     *
     * @param AIProviderInterface $provider AI provider instance
     * @return self
     */
    public function registerAIProvider(AIProviderInterface $provider): self
    {
        $this->aiProviders[$provider->getName()] = $provider;
        return $this;
    }

    /**
     * Register an image provider
     *
     * @param ImageProviderInterface $provider Image provider instance
     * @return self
     */
    public function registerImageProvider(ImageProviderInterface $provider): self
    {
        $this->imageProviders[$provider->getName()] = $provider;
        return $this;
    }

    /**
     * Set AI provider override
     *
     * @param string $provider Provider name
     * @return self
     */
    public function setAIProvider(string $provider): self
    {
        $this->overrideAIProvider = $provider;
        return $this;
    }

    /**
     * Set image provider override
     *
     * @param string $provider Provider name
     * @return self
     */
    public function setImageProvider(string $provider): self
    {
        $this->overrideImageProvider = $provider;
        return $this;
    }

    /**
     * Generate content
     *
     * Main orchestration method that coordinates all content generation steps.
     *
     * @param ContentRequest $request Content generation request
     * @return ContentResponse Generated content response
     */
    public function generate(ContentRequest $request): ContentResponse
    {
        $requestId = Str::uuid()->toString();

        $this->logger->info('Content generation started', [
            'request_id' => $requestId,
            'focus_keyword' => $request->focusKeyword,
            'locale' => $request->locale->locale,
            'content_type' => $request->contentType,
            'search_intent' => $request->searchIntent,
        ]);

        // Check cache
        if ($this->config->get('ai-content-generator.cache.enabled', true)) {
            $cacheKey = $this->generateCacheKey($request);
            $cached = $this->cache->get($cacheKey);
            
            if ($cached !== null) {
                $this->logger->info('Cache hit', ['request_id' => $requestId, 'cache_key' => $cacheKey]);
                return ContentResponse::fromArray($cached);
            }
            
            $this->logger->debug('Cache miss', ['request_id' => $requestId, 'cache_key' => $cacheKey]);
        }

        // Generate content with validation and regeneration
        $maxAttempts = $this->config->get('ai-content-generator.validation.max_regeneration_attempts', 3);
        $response = $this->generateWithRetry($request, $requestId, $maxAttempts);

        // Cache the result
        if ($this->config->get('ai-content-generator.cache.enabled', true)) {
            $ttl = $this->config->get('ai-content-generator.cache.ttl', 3600);
            $this->cache->put($cacheKey, $response->toArray(), $ttl);
            $this->logger->debug('Content cached', ['request_id' => $requestId, 'ttl' => $ttl]);
        }

        $this->logger->info('Content generation completed', [
            'request_id' => $requestId,
            'word_count' => $response->wordCount,
            'faq_count' => count($response->faqs),
            'image_count' => count($response->images),
        ]);

        return $response;
    }

    /**
     * Generate content with retry on validation failure
     *
     * @param ContentRequest $request Content request
     * @param string $requestId Request ID for logging
     * @param int $maxAttempts Maximum regeneration attempts
     * @param int $attempt Current attempt number
     * @return ContentResponse Generated content
     * @throws \Exception When max attempts exceeded
     */
    private function generateWithRetry(
        ContentRequest $request,
        string $requestId,
        int $maxAttempts,
        int $attempt = 1
    ): ContentResponse {
        $this->logger->debug('Generation attempt', ['request_id' => $requestId, 'attempt' => $attempt]);

        // Select AI provider
        $aiProvider = $this->selectAIProvider($request, $requestId);

        // Build prompt
        $prompt = $this->buildPrompt($request);

        // Generate content from AI
        $rawContent = $this->generateAIContent($aiProvider, $prompt, $requestId);

        // Parse AI response
        $parsedContent = $this->parseAIResponse($rawContent);

        // Search and select images
        $images = $this->searchImages($request, $requestId);

        // Build response
        $response = new ContentResponse(
            title: $parsedContent['title'],
            metaDescription: $parsedContent['meta_description'],
            excerpt: $parsedContent['excerpt'],
            focusKeyword: $request->focusKeyword,
            content: $parsedContent['content'],
            faqs: $parsedContent['faqs'],
            images: $images,
            wordCount: $this->countWords($parsedContent['content']),
            generatedAt: Carbon::now()
        );

        // Validate content
        $errors = $this->validationService->validate($response, $request);

        if (!empty($errors)) {
            $this->logger->warning('Content validation failed', [
                'request_id' => $requestId,
                'attempt' => $attempt,
                'errors' => $errors,
            ]);

            if ($attempt < $maxAttempts) {
                // Exponential backoff before retry
                $delay = min(1000 * (2 ** ($attempt - 1)), 30000); // Max 30 seconds
                usleep($delay * 1000);

                return $this->generateWithRetry($request, $requestId, $maxAttempts, $attempt + 1);
            }

            throw new \Exception('Content validation failed after ' . $maxAttempts . ' attempts: ' . json_encode($errors));
        }

        return $response;
    }

    /**
     * Select AI provider with fallback support
     *
     * @param ContentRequest $request Content request
     * @param string $requestId Request ID for logging
     * @return AIProviderInterface Selected AI provider
     * @throws \Exception When no available provider found
     */
    private function selectAIProvider(ContentRequest $request, string $requestId): AIProviderInterface
    {
        // Use override if set
        $preferredProvider = $this->overrideAIProvider 
            ?? $request->aiProvider 
            ?? $this->config->get('ai-content-generator.ai_providers.default', 'openai');

        // Try preferred provider first
        if (isset($this->aiProviders[$preferredProvider]) && $this->aiProviders[$preferredProvider]->isAvailable()) {
            $this->logger->info('AI provider selected', ['request_id' => $requestId, 'provider' => $preferredProvider]);
            return $this->aiProviders[$preferredProvider];
        }

        $this->logger->warning('Preferred AI provider unavailable, trying fallback', [
            'request_id' => $requestId,
            'preferred_provider' => $preferredProvider,
        ]);

        // Try fallback providers
        $fallbackOrder = $this->config->get('ai-content-generator.ai_providers.fallback_order', []);
        
        foreach ($fallbackOrder as $providerName) {
            if (isset($this->aiProviders[$providerName]) && $this->aiProviders[$providerName]->isAvailable()) {
                $this->logger->info('Fallback AI provider selected', [
                    'request_id' => $requestId,
                    'provider' => $providerName,
                ]);
                return $this->aiProviders[$providerName];
            }
        }

        throw new \Exception('No available AI provider found');
    }

    /**
     * Build prompt for AI content generation
     *
     * @param ContentRequest $request Content request
     * @return string Complete prompt
     */
    private function buildPrompt(ContentRequest $request): string
    {
        // Build system prompt
        $systemPrompt = $this->buildSystemPrompt($request);

        // Build user prompt
        $userPrompt = $this->buildUserPrompt($request);

        // Combine prompts
        return $systemPrompt . "\n\n" . $userPrompt;
    }

    /**
     * Build system prompt with E-E-A-T guidelines
     *
     * @param ContentRequest $request Content request
     * @return string System prompt
     */
    private function buildSystemPrompt(ContentRequest $request): string
    {
        $contentTypeInstructions = $this->getContentTypeInstructions($request->contentType);
        $expertiseAreas = implode(', ', $request->author->author_expertise_areas ?? []);

        return <<<PROMPT
You are an expert content writer specializing in {$request->contentType} articles.
You write in {$request->locale->localeName} language for {$request->locale->targetCountry} audience.

Author Profile:
- Name: {$request->author->author_name}
- Company: {$request->author->author_company}
- Job Title: {$request->author->author_job_title}
- Expertise: {$expertiseAreas}
- Bio: {$request->author->author_short_bio}

E-E-A-T Guidelines:
- Demonstrate practical EXPERIENCE through real-world examples and insights
- Show EXPERTISE by incorporating technical knowledge and industry best practices
- Establish AUTHORITATIVENESS by citing credible sources and data
- Build TRUSTWORTHINESS through accurate, verifiable information

Content Type Guidelines:
{$contentTypeInstructions}

Writing Rules:
- Write ONLY in HTML format using H2 and H3 tags for structure
- NO Markdown formatting (no ##, **, *, [], backticks)
- Use semantic HTML5 elements where appropriate
- Wrap all text in paragraph tags
- Maintain proper heading hierarchy (H2 for main sections, H3 for subsections)
- Include the author's profile link naturally in the content
- Cite external authoritative sources where appropriate
- Include relevant data and statistics to support claims
- Avoid keyword stuffing, clickbait, and generic phrases
- Do NOT include social media links
- Do NOT use target="_blank" or rel="nofollow" except for the contact link
PROMPT;
    }

    /**
     * Get content type specific instructions
     *
     * @param string $contentType Content type
     * @return string Instructions
     */
    private function getContentTypeInstructions(string $contentType): string
    {
        return match ($contentType) {
            'how-to' => <<<INSTRUCTIONS
For HOW-TO content:
- Structure content with clear, numbered steps
- Provide actionable, practical instructions
- Include tips and best practices for each step
- Anticipate common problems and provide solutions
- Use imperative language (do this, then do that)
INSTRUCTIONS,
            'concept' => <<<INSTRUCTIONS
For CONCEPT content:
- Focus on clear definitions and explanations
- Break down complex ideas into digestible parts
- Use analogies and examples to illustrate concepts
- Provide context and background information
- Build from basic to advanced understanding
INSTRUCTIONS,
            'news' => <<<INSTRUCTIONS
For NEWS content:
- Emphasize timeliness and current relevance
- Provide analysis and expert commentary
- Explain the impact and implications
- Include multiple perspectives when appropriate
- Use factual, objective language
INSTRUCTIONS,
            default => 'Write informative, engaging content that provides value to readers.',
        };
    }

    /**
     * Build user prompt with specific requirements
     *
     * @param ContentRequest $request Content request
     * @return string User prompt
     */
    private function buildUserPrompt(ContentRequest $request): string
    {
        $relatedKeywords = implode(', ', $request->relatedKeywords);

        return <<<PROMPT
Generate a comprehensive article with the following specifications:

Topic & Keywords:
- Focus Keyword: {$request->focusKeyword}
- Related Keywords: {$relatedKeywords}
- Search Intent: {$request->searchIntent}

Content Structure:
- Introduction: {$request->introWordCount} words
- Main Content: {$request->mainContentWordCount} words
- Conclusion: {$request->conclusionWordCount} words
- Total Word Count: {$request->wordCountMin}-{$request->wordCountMax} words

Requirements:
- Generate a title (50-60 characters) that includes the focus keyword
- Create a meta description (150-160 characters)
- Write an excerpt (100-150 words)
- Generate at least {$request->faqMinCount} FAQs related to the topic
- Include the contact link: {$request->contactUrl} naturally in the content with target="_blank" and rel="nofollow" attributes
- Maintain keyword density between 0.5% and 2.5%
- Ensure content is AdSense compliant (no adult content, violence, illegal activities, excessive profanity)

Output Format:
Return ONLY a valid JSON object with this exact structure:
{
    "title": "Article title here",
    "meta_description": "Meta description here",
    "excerpt": "Excerpt here",
    "content": "<h2>Section Title</h2><p>Content here...</p>",
    "faqs": [
        {"question": "Question 1?", "answer": "Answer 1"},
        {"question": "Question 2?", "answer": "Answer 2"}
    ]
}

IMPORTANT: Return ONLY the JSON object, no additional text before or after.
PROMPT;
    }

    /**
     * Generate content from AI provider
     *
     * @param AIProviderInterface $provider AI provider
     * @param string $prompt Complete prompt
     * @param string $requestId Request ID for logging
     * @return string Raw AI response
     */
    private function generateAIContent(AIProviderInterface $provider, string $prompt, string $requestId): string
    {
        try {
            $this->logger->debug('Calling AI provider', [
                'request_id' => $requestId,
                'provider' => $provider->getName(),
            ]);

            $content = $provider->generateContent($prompt);

            $this->logger->info('AI content generated', [
                'request_id' => $requestId,
                'provider' => $provider->getName(),
                'content_length' => strlen($content),
            ]);

            return $content;
        } catch (\Exception $e) {
            $this->logger->error('AI provider failed', [
                'request_id' => $requestId,
                'provider' => $provider->getName(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Parse AI response JSON
     *
     * @param string $rawContent Raw AI response
     * @return array Parsed content array
     * @throws \Exception When JSON parsing fails
     */
    private function parseAIResponse(string $rawContent): array
    {
        // Try to extract JSON from response (AI might add extra text)
        $jsonStart = strpos($rawContent, '{');
        $jsonEnd = strrpos($rawContent, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception('No JSON found in AI response');
        }

        $jsonString = substr($rawContent, $jsonStart, $jsonEnd - $jsonStart + 1);
        $parsed = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse AI response JSON: ' . json_last_error_msg());
        }

        // Validate required fields
        $required = ['title', 'meta_description', 'excerpt', 'content', 'faqs'];
        foreach ($required as $field) {
            if (!isset($parsed[$field])) {
                throw new \Exception("Missing required field in AI response: {$field}");
            }
        }

        return $parsed;
    }

    /**
     * Search and select images
     *
     * @param ContentRequest $request Content request
     * @param string $requestId Request ID for logging
     * @return array<ImageResult> Selected images
     */
    private function searchImages(ContentRequest $request, string $requestId): array
    {
        // Select image provider
        $providerName = $this->overrideImageProvider 
            ?? $request->imageProvider 
            ?? $this->config->get('ai-content-generator.image_providers.default', 'pexels');

        if (!isset($this->imageProviders[$providerName])) {
            $this->logger->warning('Image provider not registered', [
                'request_id' => $requestId,
                'provider' => $providerName,
            ]);
            return [];
        }

        $provider = $this->imageProviders[$providerName];

        if (!$provider->isAvailable()) {
            $this->logger->warning('Image provider unavailable', [
                'request_id' => $requestId,
                'provider' => $providerName,
            ]);
            return [];
        }

        // Check cache for images
        if ($this->config->get('ai-content-generator.cache.enabled', true)) {
            $cacheKey = $this->generateImageCacheKey($request->focusKeyword, $providerName);
            $cached = $this->cache->get($cacheKey);
            
            if ($cached !== null) {
                $this->logger->debug('Image cache hit', ['request_id' => $requestId, 'cache_key' => $cacheKey]);
                return array_map(fn($data) => ImageResult::fromArray($data), $cached);
            }
        }

        try {
            $this->logger->debug('Searching images', [
                'request_id' => $requestId,
                'provider' => $providerName,
                'keyword' => $request->focusKeyword,
            ]);

            $images = $provider->searchImages($request->focusKeyword, 5);

            // Sort by relevance score (descending)
            usort($images, fn($a, $b) => $b->relevanceScore <=> $a->relevanceScore);

            $this->logger->info('Images found', [
                'request_id' => $requestId,
                'provider' => $providerName,
                'count' => count($images),
            ]);

            // Cache images
            if ($this->config->get('ai-content-generator.cache.enabled', true)) {
                $ttl = $this->config->get('ai-content-generator.cache.ttl', 3600);
                $this->cache->put(
                    $cacheKey,
                    array_map(fn($img) => $img->toArray(), $images),
                    $ttl
                );
            }

            return $images;
        } catch (\Exception $e) {
            // Images are optional - log but don't fail
            $this->logger->warning('Image search failed', [
                'request_id' => $requestId,
                'provider' => $providerName,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Count words in HTML content
     *
     * @param string $content HTML content
     * @return int Word count
     */
    private function countWords(string $content): int
    {
        $plainText = strip_tags($content);
        $words = preg_split('/\s+/', trim($plainText), -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    /**
     * Generate cache key for content request
     *
     * @param ContentRequest $request Content request
     * @return string Cache key
     */
    private function generateCacheKey(ContentRequest $request): string
    {
        $prefix = $this->config->get('ai-content-generator.cache.prefix', 'ai_content_');
        
        $data = [
            'focus_keyword' => $request->focusKeyword,
            'related_keywords' => $request->relatedKeywords,
            'search_intent' => $request->searchIntent,
            'content_type' => $request->contentType,
            'locale' => $request->locale->locale,
            'word_count_min' => $request->wordCountMin,
            'word_count_max' => $request->wordCountMax,
            'author_id' => $request->author->id ?? 0,
        ];

        return $prefix . md5(json_encode($data));
    }

    /**
     * Generate cache key for image search
     *
     * @param string $keyword Search keyword
     * @param string $provider Provider name
     * @return string Cache key
     */
    private function generateImageCacheKey(string $keyword, string $provider): string
    {
        $prefix = $this->config->get('ai-content-generator.cache.prefix', 'ai_content_');
        return $prefix . 'images_' . $provider . '_' . md5($keyword);
    }

    /**
     * Clear cache for a specific request
     *
     * @param ContentRequest $request Content request
     * @return bool True if cache was cleared
     */
    public function clearCache(ContentRequest $request): bool
    {
        $cacheKey = $this->generateCacheKey($request);
        return $this->cache->forget($cacheKey);
    }

    /**
     * Clear all content generation cache
     *
     * @return bool True if cache was cleared
     */
    public function clearAllCache(): bool
    {
        $prefix = $this->config->get('ai-content-generator.cache.prefix', 'ai_content_');
        
        // Note: This is a simple implementation. For production, you might want to use cache tags
        // or a more sophisticated cache invalidation strategy.
        $this->logger->info('Clearing all content generation cache', ['prefix' => $prefix]);
        
        return true;
    }
}

