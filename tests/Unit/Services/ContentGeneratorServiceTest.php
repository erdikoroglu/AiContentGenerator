<?php

namespace ErdiKoroglu\AIContentGenerator\Tests\Unit\Services;

use Carbon\Carbon;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentResponse;
use ErdiKoroglu\AIContentGenerator\DTOs\ImageResult;
use ErdiKoroglu\AIContentGenerator\DTOs\LocaleConfiguration;
use ErdiKoroglu\AIContentGenerator\Models\AuthorPersona;
use ErdiKoroglu\AIContentGenerator\Providers\AI\AIProviderInterface;
use ErdiKoroglu\AIContentGenerator\Providers\Image\ImageProviderInterface;
use ErdiKoroglu\AIContentGenerator\Services\ContentGeneratorService;
use ErdiKoroglu\AIContentGenerator\Services\KeywordAnalyzerService;
use ErdiKoroglu\AIContentGenerator\Services\ValidationService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ContentGeneratorServiceTest extends TestCase
{
    private ContentGeneratorService $service;
    private ConfigRepository $config;
    private CacheRepository $cache;
    private ValidationService $validationService;
    private KeywordAnalyzerService $keywordAnalyzer;
    private LoggerInterface $logger;
    private AIProviderInterface $aiProvider;
    private ImageProviderInterface $imageProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Mockery::mock(ConfigRepository::class);
        $this->cache = Mockery::mock(CacheRepository::class);
        $this->validationService = Mockery::mock(ValidationService::class);
        $this->keywordAnalyzer = Mockery::mock(KeywordAnalyzerService::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->aiProvider = Mockery::mock(AIProviderInterface::class);
        $this->imageProvider = Mockery::mock(ImageProviderInterface::class);

        // Allow any logging calls
        $this->logger->shouldReceive('info')->andReturnNull();
        $this->logger->shouldReceive('debug')->andReturnNull();
        $this->logger->shouldReceive('warning')->andReturnNull();
        $this->logger->shouldReceive('error')->andReturnNull();

        $this->service = new ContentGeneratorService(
            $this->config,
            $this->cache,
            $this->validationService,
            $this->keywordAnalyzer,
            $this->logger
        );

        // Register providers
        $this->aiProvider->shouldReceive('getName')->andReturn('openai');
        $this->imageProvider->shouldReceive('getName')->andReturn('pexels');
        
        $this->service->registerAIProvider($this->aiProvider);
        $this->service->registerImageProvider($this->imageProvider);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_set_ai_provider_override(): void
    {
        $result = $this->service->setAIProvider('google');
        
        $this->assertInstanceOf(ContentGeneratorService::class, $result);
    }

    public function test_can_set_image_provider_override(): void
    {
        $result = $this->service->setImageProvider('pixabay');
        
        $this->assertInstanceOf(ContentGeneratorService::class, $result);
    }

    public function test_generates_content_successfully(): void
    {
        // Setup
        $request = $this->createContentRequest();
        
        // Mock config
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.cache.enabled', true)
            ->andReturn(false);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.validation.max_regeneration_attempts', 3)
            ->andReturn(3);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.ai_providers.default', 'openai')
            ->andReturn('openai');
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.ai_providers.fallback_order', [])
            ->andReturn([]);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.image_providers.default', 'pexels')
            ->andReturn('pexels');

        // Mock AI provider
        $this->aiProvider->shouldReceive('isAvailable')->andReturn(true);
        $this->aiProvider->shouldReceive('generateContent')
            ->once()
            ->andReturn($this->createMockAIResponse());

        // Mock image provider
        $this->imageProvider->shouldReceive('isAvailable')->andReturn(true);
        $this->imageProvider->shouldReceive('searchImages')
            ->once()
            ->andReturn([
                new ImageResult(
                    url: 'https://example.com/image.jpg',
                    altText: 'Test image',
                    attribution: null,
                    relevanceScore: 0.9,
                    width: 1920,
                    height: 1080
                )
            ]);

        // Mock validation
        $this->validationService->shouldReceive('validate')
            ->once()
            ->andReturn([]);

        // Execute
        $response = $this->service->generate($request);

        // Assert
        $this->assertInstanceOf(ContentResponse::class, $response);
        $this->assertEquals('Test Article Title', $response->title);
        $this->assertEquals('Laravel', $response->focusKeyword);
        $this->assertGreaterThan(0, $response->wordCount);
        $this->assertCount(3, $response->faqs);
        $this->assertCount(1, $response->images);
    }

    public function test_uses_cache_when_enabled(): void
    {
        // Setup
        $request = $this->createContentRequest();
        $cachedResponse = [
            'title' => 'Cached Title',
            'meta_description' => 'Cached description',
            'excerpt' => 'Cached excerpt',
            'focus_keyword' => 'Laravel',
            'content' => '<h2>Cached Content</h2>',
            'faqs' => [],
            'images' => [],
            'word_count' => 100,
            'generated_at' => Carbon::now()->toIso8601String(),
        ];

        // Mock config
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.cache.enabled', true)
            ->andReturn(true);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.cache.prefix', 'ai_content_')
            ->andReturn('ai_content_');

        // Mock cache hit
        $this->cache->shouldReceive('get')
            ->once()
            ->andReturn($cachedResponse);

        // Execute
        $response = $this->service->generate($request);

        // Assert
        $this->assertEquals('Cached Title', $response->title);
    }

    public function test_falls_back_to_secondary_provider_when_primary_unavailable(): void
    {
        // Setup
        $request = $this->createContentRequest();
        $secondaryProvider = Mockery::mock(AIProviderInterface::class);
        $secondaryProvider->shouldReceive('getName')->andReturn('google');
        $this->service->registerAIProvider($secondaryProvider);

        // Mock config
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.cache.enabled', true)
            ->andReturn(false);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.validation.max_regeneration_attempts', 3)
            ->andReturn(3);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.ai_providers.default', 'openai')
            ->andReturn('openai');
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.ai_providers.fallback_order', [])
            ->andReturn(['google']);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.image_providers.default', 'pexels')
            ->andReturn('pexels');

        // Primary provider unavailable
        $this->aiProvider->shouldReceive('isAvailable')->andReturn(false);

        // Secondary provider available
        $secondaryProvider->shouldReceive('isAvailable')->andReturn(true);
        $secondaryProvider->shouldReceive('generateContent')
            ->once()
            ->andReturn($this->createMockAIResponse());

        // Mock image provider
        $this->imageProvider->shouldReceive('isAvailable')->andReturn(false);

        // Mock validation
        $this->validationService->shouldReceive('validate')
            ->once()
            ->andReturn([]);

        // Execute
        $response = $this->service->generate($request);

        // Assert
        $this->assertInstanceOf(ContentResponse::class, $response);
    }

    public function test_retries_on_validation_failure(): void
    {
        // Setup
        $request = $this->createContentRequest();

        // Mock config
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.cache.enabled', true)
            ->andReturn(false);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.validation.max_regeneration_attempts', 3)
            ->andReturn(3);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.ai_providers.default', 'openai')
            ->andReturn('openai');
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.ai_providers.fallback_order', [])
            ->andReturn([]);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.image_providers.default', 'pexels')
            ->andReturn('pexels');

        // Mock AI provider
        $this->aiProvider->shouldReceive('isAvailable')->andReturn(true);
        $this->aiProvider->shouldReceive('generateContent')
            ->twice()
            ->andReturn($this->createMockAIResponse());

        // Mock image provider
        $this->imageProvider->shouldReceive('isAvailable')->andReturn(false);

        // Mock validation - fail first, pass second
        $this->validationService->shouldReceive('validate')
            ->once()
            ->andReturn(['error' => ['Validation failed']]);
        $this->validationService->shouldReceive('validate')
            ->once()
            ->andReturn([]);

        // Execute
        $response = $this->service->generate($request);

        // Assert
        $this->assertInstanceOf(ContentResponse::class, $response);
    }

    public function test_handles_image_search_failure_gracefully(): void
    {
        // Setup
        $request = $this->createContentRequest();

        // Mock config
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.cache.enabled', true)
            ->andReturn(false);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.validation.max_regeneration_attempts', 3)
            ->andReturn(3);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.ai_providers.default', 'openai')
            ->andReturn('openai');
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.ai_providers.fallback_order', [])
            ->andReturn([]);
        $this->config->shouldReceive('get')
            ->with('ai-content-generator.image_providers.default', 'pexels')
            ->andReturn('pexels');

        // Mock AI provider
        $this->aiProvider->shouldReceive('isAvailable')->andReturn(true);
        $this->aiProvider->shouldReceive('generateContent')
            ->once()
            ->andReturn($this->createMockAIResponse());

        // Mock image provider to throw exception
        $this->imageProvider->shouldReceive('isAvailable')->andReturn(true);
        $this->imageProvider->shouldReceive('searchImages')
            ->once()
            ->andThrow(new \Exception('Image search failed'));

        // Mock validation
        $this->validationService->shouldReceive('validate')
            ->once()
            ->andReturn([]);

        // Execute
        $response = $this->service->generate($request);

        // Assert - should succeed without images
        $this->assertInstanceOf(ContentResponse::class, $response);
        $this->assertCount(0, $response->images);
    }

    private function createContentRequest(): ContentRequest
    {
        $author = new AuthorPersona();
        $author->id = 1;
        $author->author_name = 'John Doe';
        $author->author_company = 'Tech Corp';
        $author->author_job_title = 'Senior Developer';
        $author->author_expertise_areas = ['Laravel', 'PHP', 'Web Development'];
        $author->author_short_bio = 'Experienced developer with 10 years in web development.';
        $author->author_url = 'https://example.com/author';

        return new ContentRequest(
            focusKeyword: 'Laravel',
            relatedKeywords: ['PHP', 'Framework', 'Web Development'],
            searchIntent: 'informational',
            contentType: 'concept',
            locale: new LocaleConfiguration('en_US', 'English', 'US', 'USD'),
            author: $author,
            wordCountMin: 800,
            wordCountMax: 1500,
            introWordCount: 100,
            conclusionWordCount: 100,
            mainContentWordCount: 600,
            faqMinCount: 3,
            contactUrl: 'https://example.com/contact'
        );
    }

    private function createMockAIResponse(): string
    {
        return json_encode([
            'title' => 'Test Article Title',
            'meta_description' => 'This is a test meta description for the article.',
            'excerpt' => 'This is a test excerpt that provides a brief overview of the article content.',
            'content' => '<h2>Introduction</h2><p>This is the introduction paragraph.</p><h2>Main Content</h2><p>This is the main content paragraph with more details.</p><h2>Conclusion</h2><p>This is the conclusion paragraph.</p>',
            'faqs' => [
                ['question' => 'What is Laravel?', 'answer' => 'Laravel is a PHP framework.'],
                ['question' => 'Why use Laravel?', 'answer' => 'Laravel provides elegant syntax and powerful features.'],
                ['question' => 'How to install Laravel?', 'answer' => 'Use Composer to install Laravel.'],
            ],
        ]);
    }
}

