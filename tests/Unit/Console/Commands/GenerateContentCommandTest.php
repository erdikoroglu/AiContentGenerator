<?php

use ErdiKoroglu\AIContentGenerator\Console\Commands\GenerateContentCommand;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentResponse;
use ErdiKoroglu\AIContentGenerator\Models\AuthorPersona;
use ErdiKoroglu\AIContentGenerator\Services\ContentGeneratorService;
use Illuminate\Support\Facades\File;

uses()->group('command');

beforeEach(function () {
    // Run migrations
    $this->artisan('migrate', ['--database' => 'testing'])->run();
    
    // Create test author persona
    $this->author = AuthorPersona::create([
        'author_name' => 'Test Author',
        'author_company' => 'Test Company',
        'author_job_title' => 'Test Writer',
        'author_expertise_areas' => ['Testing', 'PHP'],
        'author_short_bio' => 'Test bio for testing purposes.',
        'author_url' => 'https://example.com/author',
    ]);
});

afterEach(function () {
    // Clean up test files
    if (File::exists('test-output.json')) {
        File::delete('test-output.json');
    }
});

test('command requires focus keyword argument', function () {
    $this->artisan('ai-content:generate')
        ->expectsOutput('Not enough arguments (missing: "focus-keyword").')
        ->assertFailed();
});

test('command requires contact-url option', function () {
    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
    ])
        ->expectsOutput('The --contact-url option is required.')
        ->assertFailed();
});

test('command validates contact-url format', function () {
    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'invalid-url',
    ])
        ->expectsOutput('The --contact-url must be a valid URL.')
        ->assertFailed();
});

test('command validates search intent', function () {
    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
        '--search-intent' => 'invalid',
    ])
        ->assertFailed();
});

test('command validates content type', function () {
    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
        '--content-type' => 'invalid',
    ])
        ->assertFailed();
});

test('command validates word count range', function () {
    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
        '--word-count-min' => 1500,
        '--word-count-max' => 800,
    ])
        ->expectsOutput('Minimum word count must be less than maximum word count.')
        ->assertFailed();
});

test('command validates faq count minimum', function () {
    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
        '--faq-count' => 2,
    ])
        ->expectsOutput('FAQ count must be at least 3.')
        ->assertFailed();
});

test('command generates content successfully with minimal options', function () {
    // Mock the content generator service
    $mockResponse = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test meta description for testing purposes.',
        excerpt: 'Test excerpt for testing purposes.',
        focusKeyword: 'Laravel Tutorial',
        content: '<h2>Test Content</h2><p>This is test content.</p>',
        faqs: [
            ['question' => 'What is Laravel?', 'answer' => 'Laravel is a PHP framework.'],
        ],
        images: [],
        wordCount: 850,
        generatedAt: now()
    );

    $mockGenerator = Mockery::mock(ContentGeneratorService::class);
    $mockGenerator->shouldReceive('generate')
        ->once()
        ->andReturn($mockResponse);

    $this->app->instance(ContentGeneratorService::class, $mockGenerator);

    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
    ])
        ->expectsOutput('✓ Content generated successfully!')
        ->assertSuccessful();
});

test('command generates content with all options', function () {
    $mockResponse = new ContentResponse(
        title: 'Complete Laravel Tutorial',
        metaDescription: 'A comprehensive guide to Laravel framework for beginners.',
        excerpt: 'Learn Laravel from scratch with this detailed tutorial.',
        focusKeyword: 'Laravel Tutorial',
        content: '<h2>Introduction</h2><p>Laravel is a powerful PHP framework.</p>',
        faqs: [
            ['question' => 'What is Laravel?', 'answer' => 'Laravel is a PHP framework.'],
            ['question' => 'Why use Laravel?', 'answer' => 'Laravel simplifies web development.'],
            ['question' => 'How to install Laravel?', 'answer' => 'Use Composer to install Laravel.'],
        ],
        images: [],
        wordCount: 1200,
        generatedAt: now()
    );

    $mockGenerator = Mockery::mock(ContentGeneratorService::class);
    $mockGenerator->shouldReceive('generate')
        ->once()
        ->andReturn($mockResponse);

    $this->app->instance(ContentGeneratorService::class, $mockGenerator);

    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--related-keywords' => 'PHP,Framework,Web Development',
        '--search-intent' => 'informational',
        '--content-type' => 'how-to',
        '--locale' => 'en_US',
        '--author-id' => $this->author->id,
        '--word-count-min' => 1000,
        '--word-count-max' => 2000,
        '--faq-count' => 5,
        '--contact-url' => 'https://example.com/contact',
        '--ai-provider' => 'openai',
        '--image-provider' => 'pexels',
    ])
        ->expectsOutput('✓ Content generated successfully!')
        ->assertSuccessful();
});

test('command outputs to file when output option is provided', function () {
    $mockResponse = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test meta description.',
        excerpt: 'Test excerpt.',
        focusKeyword: 'Laravel Tutorial',
        content: '<h2>Test</h2>',
        faqs: [],
        images: [],
        wordCount: 800,
        generatedAt: now()
    );

    $mockGenerator = Mockery::mock(ContentGeneratorService::class);
    $mockGenerator->shouldReceive('generate')
        ->once()
        ->andReturn($mockResponse);

    $this->app->instance(ContentGeneratorService::class, $mockGenerator);

    $outputPath = 'test-output.json';

    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
        '--output' => $outputPath,
    ])
        ->expectsOutput("Content saved to: {$outputPath}")
        ->assertSuccessful();

    expect(File::exists($outputPath))->toBeTrue();
    
    $content = json_decode(File::get($outputPath), true);
    expect($content)->toHaveKey('title')
        ->and($content['title'])->toBe('Test Title');
});

test('command outputs to stdout when no output option is provided', function () {
    $mockResponse = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test meta description.',
        excerpt: 'Test excerpt.',
        focusKeyword: 'Laravel Tutorial',
        content: '<h2>Test</h2>',
        faqs: [],
        images: [],
        wordCount: 800,
        generatedAt: now()
    );

    $mockGenerator = Mockery::mock(ContentGeneratorService::class);
    $mockGenerator->shouldReceive('generate')
        ->once()
        ->andReturn($mockResponse);

    $this->app->instance(ContentGeneratorService::class, $mockGenerator);

    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
    ])
        ->expectsOutputToContain('"title": "Test Title"')
        ->assertSuccessful();
});

test('command handles generation errors gracefully', function () {
    $mockGenerator = Mockery::mock(ContentGeneratorService::class);
    $mockGenerator->shouldReceive('generate')
        ->once()
        ->andThrow(new \Exception('Test error message'));

    $this->app->instance(ContentGeneratorService::class, $mockGenerator);

    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
    ])
        ->expectsOutput('Error generating content: Test error message')
        ->assertFailed();
});

test('command uses default author when no author-id provided', function () {
    $mockResponse = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test meta description.',
        excerpt: 'Test excerpt.',
        focusKeyword: 'Laravel Tutorial',
        content: '<h2>Test</h2>',
        faqs: [],
        images: [],
        wordCount: 800,
        generatedAt: now()
    );

    $mockGenerator = Mockery::mock(ContentGeneratorService::class);
    $mockGenerator->shouldReceive('generate')
        ->once()
        ->with(Mockery::on(function ($request) {
            return $request->author->author_name === 'Content Writer';
        }))
        ->andReturn($mockResponse);

    $this->app->instance(ContentGeneratorService::class, $mockGenerator);

    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
    ])
        ->assertSuccessful();
});

test('command warns when author-id not found', function () {
    $mockResponse = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test meta description.',
        excerpt: 'Test excerpt.',
        focusKeyword: 'Laravel Tutorial',
        content: '<h2>Test</h2>',
        faqs: [],
        images: [],
        wordCount: 800,
        generatedAt: now()
    );

    $mockGenerator = Mockery::mock(ContentGeneratorService::class);
    $mockGenerator->shouldReceive('generate')
        ->once()
        ->andReturn($mockResponse);

    $this->app->instance(ContentGeneratorService::class, $mockGenerator);

    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
        '--author-id' => 99999,
    ])
        ->expectsOutput('Author persona with ID 99999 not found. Using default author.')
        ->assertSuccessful();
});

test('command parses related keywords correctly', function () {
    $mockResponse = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test meta description.',
        excerpt: 'Test excerpt.',
        focusKeyword: 'Laravel Tutorial',
        content: '<h2>Test</h2>',
        faqs: [],
        images: [],
        wordCount: 800,
        generatedAt: now()
    );

    $mockGenerator = Mockery::mock(ContentGeneratorService::class);
    $mockGenerator->shouldReceive('generate')
        ->once()
        ->with(Mockery::on(function ($request) {
            return $request->relatedKeywords === ['PHP', 'Framework', 'Web'];
        }))
        ->andReturn($mockResponse);

    $this->app->instance(ContentGeneratorService::class, $mockGenerator);

    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
        '--related-keywords' => 'PHP, Framework, Web',
    ])
        ->assertSuccessful();
});

test('command builds correct locale configuration', function () {
    $mockResponse = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test meta description.',
        excerpt: 'Test excerpt.',
        focusKeyword: 'Laravel Tutorial',
        content: '<h2>Test</h2>',
        faqs: [],
        images: [],
        wordCount: 800,
        generatedAt: now()
    );

    $mockGenerator = Mockery::mock(ContentGeneratorService::class);
    $mockGenerator->shouldReceive('generate')
        ->once()
        ->with(Mockery::on(function ($request) {
            return $request->locale->locale === 'tr_TR'
                && $request->locale->localeName === 'Türkçe'
                && $request->locale->targetCountry === 'TR'
                && $request->locale->currency === 'TRY';
        }))
        ->andReturn($mockResponse);

    $this->app->instance(ContentGeneratorService::class, $mockGenerator);

    $this->artisan('ai-content:generate', [
        'focus-keyword' => 'Laravel Tutorial',
        '--contact-url' => 'https://example.com/contact',
        '--locale' => 'tr_TR',
    ])
        ->assertSuccessful();
});
