<?php

use ErdiKoroglu\AIContentGenerator\Providers\AI\MockAIProvider;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderUnavailableException;

test('mock AI provider returns default content', function () {
    $provider = new MockAIProvider();
    
    $content = $provider->generateContent('test prompt');
    
    expect($content)->toBeString()
        ->and($content)->not->toBeEmpty()
        ->and(json_decode($content, true))->toBeArray()
        ->and(json_decode($content, true))->toHaveKeys(['title', 'meta_description', 'excerpt', 'content', 'faqs']);
});

test('mock AI provider returns custom content', function () {
    $customContent = json_encode(['title' => 'Custom Title', 'content' => '<p>Custom content</p>']);
    $provider = new MockAIProvider($customContent);
    
    $content = $provider->generateContent('test prompt');
    
    expect($content)->toBe($customContent);
});

test('mock AI provider is available by default', function () {
    $provider = new MockAIProvider();
    
    expect($provider->isAvailable())->toBeTrue();
});

test('mock AI provider can be configured as unavailable', function () {
    $provider = new MockAIProvider(null, false);
    
    expect($provider->isAvailable())->toBeFalse();
});

test('mock AI provider returns correct name', function () {
    $provider = new MockAIProvider();
    
    expect($provider->getName())->toBe('mock');
});

test('mock AI provider validates credentials by default', function () {
    $provider = new MockAIProvider();
    
    expect($provider->validateCredentials())->toBeTrue();
});

test('mock AI provider can be configured with invalid credentials', function () {
    $provider = new MockAIProvider(null, true, false);
    
    expect($provider->validateCredentials())->toBeFalse();
});

test('mock AI provider can throw configured exception', function () {
    $provider = new MockAIProvider(null, true, true, ProviderUnavailableException::class);
    
    expect(fn() => $provider->generateContent('test'))
        ->toThrow(ProviderUnavailableException::class);
});

test('mock AI provider can be reconfigured via setters', function () {
    $provider = new MockAIProvider();
    
    $provider->setMockContent('new content')
        ->setAvailable(false)
        ->setCredentialsValid(false);
    
    expect($provider->generateContent('test'))->toBe('new content')
        ->and($provider->isAvailable())->toBeFalse()
        ->and($provider->validateCredentials())->toBeFalse();
});
