<?php

use ErdiKoroglu\AIContentGenerator\Services\ValidationService;
use ErdiKoroglu\AIContentGenerator\Validators\ValidatorInterface;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentResponse;
use ErdiKoroglu\AIContentGenerator\DTOs\LocaleConfiguration;
use ErdiKoroglu\AIContentGenerator\Models\AuthorPersona;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new ValidationService();
    
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
        wordCountMin: 800,
        wordCountMax: 1500,
        introWordCount: 100,
        conclusionWordCount: 100,
        mainContentWordCount: 600,
        faqMinCount: 3,
        contactUrl: 'https://example.com/contact'
    );
    
    // Create a sample ContentResponse
    $this->response = new ContentResponse(
        title: 'Test Title',
        metaDescription: 'Test description',
        excerpt: 'Test excerpt',
        focusKeyword: 'Laravel',
        content: '<h2>Test Content</h2><p>This is test content about Laravel.</p>',
        faqs: [],
        images: [],
        wordCount: 10,
        generatedAt: Carbon::now()
    );
});

test('can add validators to the chain', function () {
    $validator1 = Mockery::mock(ValidatorInterface::class);
    $validator2 = Mockery::mock(ValidatorInterface::class);
    
    $result = $this->service->addValidator($validator1);
    
    expect($result)->toBe($this->service)
        ->and($this->service->getValidators())->toHaveCount(1);
    
    $this->service->addValidator($validator2);
    
    expect($this->service->getValidators())->toHaveCount(2)
        ->and($this->service->getValidators()[0])->toBe($validator1)
        ->and($this->service->getValidators()[1])->toBe($validator2);
});

test('returns empty array when all validators pass', function () {
    $validator1 = Mockery::mock(ValidatorInterface::class);
    $validator1->shouldReceive('validate')
        ->once()
        ->with($this->response->content, $this->request)
        ->andReturn(true);
    
    $validator2 = Mockery::mock(ValidatorInterface::class);
    $validator2->shouldReceive('validate')
        ->once()
        ->with($this->response->content, $this->request)
        ->andReturn(true);
    
    $this->service->addValidator($validator1);
    $this->service->addValidator($validator2);
    
    $errors = $this->service->validate($this->response, $this->request);
    
    expect($errors)->toBeEmpty();
});

test('collects errors from failing validators', function () {
    $validator1 = Mockery::mock(ValidatorInterface::class);
    $validator1->shouldReceive('validate')
        ->once()
        ->with($this->response->content, $this->request)
        ->andReturn(false);
    $validator1->shouldReceive('getErrors')
        ->once()
        ->andReturn(['Error 1', 'Error 2']);
    $validator1->shouldReceive('getName')
        ->once()
        ->andReturn('TestValidator1');
    
    $validator2 = Mockery::mock(ValidatorInterface::class);
    $validator2->shouldReceive('validate')
        ->once()
        ->with($this->response->content, $this->request)
        ->andReturn(true);
    
    $this->service->addValidator($validator1);
    $this->service->addValidator($validator2);
    
    $errors = $this->service->validate($this->response, $this->request);
    
    expect($errors)->toHaveKey('TestValidator1')
        ->and($errors['TestValidator1'])->toBe(['Error 1', 'Error 2']);
});

test('collects errors from multiple failing validators', function () {
    $validator1 = Mockery::mock(ValidatorInterface::class);
    $validator1->shouldReceive('validate')
        ->once()
        ->andReturn(false);
    $validator1->shouldReceive('getErrors')
        ->once()
        ->andReturn(['Validator 1 Error']);
    $validator1->shouldReceive('getName')
        ->once()
        ->andReturn('Validator1');
    
    $validator2 = Mockery::mock(ValidatorInterface::class);
    $validator2->shouldReceive('validate')
        ->once()
        ->andReturn(false);
    $validator2->shouldReceive('getErrors')
        ->once()
        ->andReturn(['Validator 2 Error A', 'Validator 2 Error B']);
    $validator2->shouldReceive('getName')
        ->once()
        ->andReturn('Validator2');
    
    $this->service->addValidator($validator1);
    $this->service->addValidator($validator2);
    
    $errors = $this->service->validate($this->response, $this->request);
    
    expect($errors)->toHaveKeys(['Validator1', 'Validator2'])
        ->and($errors['Validator1'])->toBe(['Validator 1 Error'])
        ->and($errors['Validator2'])->toBe(['Validator 2 Error A', 'Validator 2 Error B']);
});

test('executes all validators even if some fail', function () {
    $validator1 = Mockery::mock(ValidatorInterface::class);
    $validator1->shouldReceive('validate')
        ->once()
        ->andReturn(false);
    $validator1->shouldReceive('getErrors')
        ->once()
        ->andReturn(['Error 1']);
    $validator1->shouldReceive('getName')
        ->once()
        ->andReturn('Validator1');
    
    $validator2 = Mockery::mock(ValidatorInterface::class);
    $validator2->shouldReceive('validate')
        ->once()
        ->andReturn(true);
    
    $validator3 = Mockery::mock(ValidatorInterface::class);
    $validator3->shouldReceive('validate')
        ->once()
        ->andReturn(false);
    $validator3->shouldReceive('getErrors')
        ->once()
        ->andReturn(['Error 3']);
    $validator3->shouldReceive('getName')
        ->once()
        ->andReturn('Validator3');
    
    $this->service->addValidator($validator1);
    $this->service->addValidator($validator2);
    $this->service->addValidator($validator3);
    
    $errors = $this->service->validate($this->response, $this->request);
    
    expect($errors)->toHaveKeys(['Validator1', 'Validator3'])
        ->and($errors)->not->toHaveKey('Validator2');
});

test('handles validator with empty errors array', function () {
    $validator = Mockery::mock(ValidatorInterface::class);
    $validator->shouldReceive('validate')
        ->once()
        ->andReturn(false);
    $validator->shouldReceive('getErrors')
        ->once()
        ->andReturn([]);
    
    $this->service->addValidator($validator);
    
    $errors = $this->service->validate($this->response, $this->request);
    
    expect($errors)->toBeEmpty();
});

test('method chaining works for addValidator', function () {
    $validator1 = Mockery::mock(ValidatorInterface::class);
    $validator2 = Mockery::mock(ValidatorInterface::class);
    $validator3 = Mockery::mock(ValidatorInterface::class);
    
    $this->service
        ->addValidator($validator1)
        ->addValidator($validator2)
        ->addValidator($validator3);
    
    expect($this->service->getValidators())->toHaveCount(3);
});
