<?php

use ErdiKoroglu\AIContentGenerator\Services\ValidationService;
use ErdiKoroglu\AIContentGenerator\Validators\KeywordDensityValidator;
use ErdiKoroglu\AIContentGenerator\Validators\AdSenseComplianceValidator;
use ErdiKoroglu\AIContentGenerator\Validators\HTMLStructureValidator;
use ErdiKoroglu\AIContentGenerator\Validators\WordCountValidator;
use ErdiKoroglu\AIContentGenerator\Validators\ContactLinkValidator;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentResponse;
use ErdiKoroglu\AIContentGenerator\DTOs\LocaleConfiguration;
use ErdiKoroglu\AIContentGenerator\Models\AuthorPersona;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new ValidationService();
    
    // Add all validators
    $this->service
        ->addValidator(new KeywordDensityValidator())
        ->addValidator(new AdSenseComplianceValidator())
        ->addValidator(new HTMLStructureValidator())
        ->addValidator(new WordCountValidator())
        ->addValidator(new ContactLinkValidator());
    
    // Create a sample ContentRequest
    $this->request = new ContentRequest(
        focusKeyword: 'Laravel',
        relatedKeywords: ['PHP', 'framework'],
        searchIntent: 'informational',
        contentType: 'concept',
        locale: new LocaleConfiguration('en_US', 'English', 'US', 'USD'),
        author: new AuthorPersona([
            'author_name' => 'John Doe',
            'author_company' => 'Tech Corp',
            'author_job_title' => 'Developer',
            'author_expertise_areas' => ['PHP', 'Laravel'],
            'author_short_bio' => 'Expert developer',
            'author_url' => 'https://example.com'
        ]),
        wordCountMin: 50,
        wordCountMax: 200,
        introWordCount: 20,
        conclusionWordCount: 20,
        mainContentWordCount: 60,
        faqMinCount: 3,
        contactUrl: 'https://example.com/contact'
    );
});

test('validates valid content successfully', function () {
    // Create valid content with proper keyword density (~1.5%), structure, word count, and contact link
    // Target: 100 words total, 20 intro words, Laravel appears 1-2 times per 100 words (1-2% density)
    $content = '<h2>Introduction</h2>' .
        '<p>This article discusses Laravel framework and its benefits for modern applications. ' .
        'We will explore various aspects of building robust systems.</p>' .  // ~18 words intro
        '<h2>Main Content</h2>' .
        '<p>Laravel is a popular framework that helps developers create applications efficiently. ' .
        'The framework provides many tools and features for building web applications. ' .
        'Developers appreciate the elegant syntax and comprehensive documentation available. ' .
        'Many companies use this technology for their projects and products. ' .
        'The community support is excellent and helpful for newcomers. ' .
        'You can <a href="https://example.com/contact" target="_blank" rel="nofollow">contact us</a> for more information.</p>';
    
    $response = new ContentResponse(
        title: 'Laravel Framework Guide',
        metaDescription: 'Learn about Laravel PHP framework',
        excerpt: 'Laravel is a powerful PHP framework',
        focusKeyword: 'Laravel',
        content: $content,
        faqs: [],
        images: [],
        wordCount: 100,
        generatedAt: Carbon::now()
    );
    
    $errors = $this->service->validate($response, $this->request);
    
    expect($errors)->toBeEmpty();
});

test('detects keyword stuffing', function () {
    // Create content with excessive keyword density (>2.5%)
    $content = '<h2>Laravel Laravel Laravel</h2>' .
        '<p>Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel. ' .
        'Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel. ' .
        'Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel. ' .
        'Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel Laravel. ' .
        'You can <a href="https://example.com/contact" target="_blank" rel="nofollow">contact us</a>.</p>';
    
    $response = new ContentResponse(
        title: 'Laravel Guide',
        metaDescription: 'Laravel guide',
        excerpt: 'Laravel framework',
        focusKeyword: 'Laravel',
        content: $content,
        faqs: [],
        images: [],
        wordCount: 100,
        generatedAt: Carbon::now()
    );
    
    $errors = $this->service->validate($response, $this->request);
    
    expect($errors)->toHaveKey('KeywordDensityValidator');
    
    $hasKeywordStuffingError = false;
    foreach ($errors['KeywordDensityValidator'] as $error) {
        if (str_contains($error, 'Keyword stuffing')) {
            $hasKeywordStuffingError = true;
            break;
        }
    }
    
    expect($hasKeywordStuffingError)->toBeTrue();
});

test('detects AdSense policy violations', function () {
    // Create content with adult keywords
    $content = '<h2>Introduction</h2>' .
        '<p>This content contains adult material and explicit content. ' .
        'Laravel is mentioned here too. ' .
        'But the adult content violates policies. ' .
        'You can <a href="https://example.com/contact" target="_blank" rel="nofollow">contact us</a>.</p>';
    
    $response = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test description',
        excerpt: 'Test excerpt',
        focusKeyword: 'Laravel',
        content: $content,
        faqs: [],
        images: [],
        wordCount: 50,
        generatedAt: Carbon::now()
    );
    
    $errors = $this->service->validate($response, $this->request);
    
    expect($errors)->toHaveKey('AdSenseComplianceValidator');
    
    $hasAdultContentError = false;
    foreach ($errors['AdSenseComplianceValidator'] as $error) {
        if (str_contains($error, 'adult content')) {
            $hasAdultContentError = true;
            break;
        }
    }
    
    expect($hasAdultContentError)->toBeTrue();
});

test('detects invalid HTML structure', function () {
    // Create content without proper H2 tags
    $content = '<p>This is content without proper heading structure. ' .
        'Laravel is a great framework for PHP development. ' .
        'Many developers use Laravel for their projects. ' .
        'Laravel provides elegant syntax and powerful tools. ' .
        'You can <a href="https://example.com/contact" target="_blank" rel="nofollow">contact us</a>.</p>';
    
    $response = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test description',
        excerpt: 'Test excerpt',
        focusKeyword: 'Laravel',
        content: $content,
        faqs: [],
        images: [],
        wordCount: 50,
        generatedAt: Carbon::now()
    );
    
    $errors = $this->service->validate($response, $this->request);
    
    expect($errors)->toHaveKey('HTMLStructureValidator');
    
    $hasH2Error = false;
    foreach ($errors['HTMLStructureValidator'] as $error) {
        if (str_contains($error, 'H2')) {
            $hasH2Error = true;
            break;
        }
    }
    
    expect($hasH2Error)->toBeTrue();
});

test('detects word count violations', function () {
    // Create content with too few words (request requires 50-200 words)
    $content = '<h2>Short</h2><p>Too short. <a href="https://example.com/contact" target="_blank" rel="nofollow">Contact</a>.</p>';
    
    $response = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test description',
        excerpt: 'Test excerpt',
        focusKeyword: 'Laravel',
        content: $content,
        faqs: [],
        images: [],
        wordCount: 5,
        generatedAt: Carbon::now()
    );
    
    $errors = $this->service->validate($response, $this->request);
    
    expect($errors)->toHaveKey('WordCountValidator');
    
    $hasWordCountError = false;
    foreach ($errors['WordCountValidator'] as $error) {
        if (str_contains($error, 'word count')) {
            $hasWordCountError = true;
            break;
        }
    }
    
    expect($hasWordCountError)->toBeTrue();
});

test('detects missing contact link', function () {
    // Create content without contact link
    $content = '<h2>Introduction to Laravel</h2>' .
        '<p>Laravel is a powerful PHP framework. ' .
        'Laravel provides elegant syntax. ' .
        'Many developers choose Laravel. ' .
        'The Laravel community is helpful. ' .
        'Laravel makes development enjoyable.</p>';
    
    $response = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test description',
        excerpt: 'Test excerpt',
        focusKeyword: 'Laravel',
        content: $content,
        faqs: [],
        images: [],
        wordCount: 50,
        generatedAt: Carbon::now()
    );
    
    $errors = $this->service->validate($response, $this->request);
    
    expect($errors)->toHaveKey('ContactLinkValidator');
    
    $hasContactLinkError = false;
    foreach ($errors['ContactLinkValidator'] as $error) {
        if (str_contains($error, 'contact link')) {
            $hasContactLinkError = true;
            break;
        }
    }
    
    expect($hasContactLinkError)->toBeTrue();
});

test('collects multiple validation errors', function () {
    // Create content that fails multiple validators
    $content = '<p>Short content without headings or contact link.</p>';
    
    $response = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test description',
        excerpt: 'Test excerpt',
        focusKeyword: 'Laravel',
        content: $content,
        faqs: [],
        images: [],
        wordCount: 5,
        generatedAt: Carbon::now()
    );
    
    $errors = $this->service->validate($response, $this->request);
    
    // Should have errors from multiple validators
    expect($errors)->not->toBeEmpty()
        ->and(count($errors))->toBeGreaterThan(1);
});

test('all validators are registered', function () {
    $validators = $this->service->getValidators();
    
    expect($validators)->toHaveCount(5)
        ->and($validators[0])->toBeInstanceOf(KeywordDensityValidator::class)
        ->and($validators[1])->toBeInstanceOf(AdSenseComplianceValidator::class)
        ->and($validators[2])->toBeInstanceOf(HTMLStructureValidator::class)
        ->and($validators[3])->toBeInstanceOf(WordCountValidator::class)
        ->and($validators[4])->toBeInstanceOf(ContactLinkValidator::class);
});
