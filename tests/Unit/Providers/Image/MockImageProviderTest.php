<?php

use ErdiKoroglu\AIContentGenerator\Providers\Image\MockImageProvider;
use ErdiKoroglu\AIContentGenerator\DTOs\ImageResult;
use ErdiKoroglu\AIContentGenerator\Exceptions\ImageProviderException;

test('mock image provider returns default images', function () {
    $provider = new MockImageProvider();
    
    $images = $provider->searchImages('test keyword');
    
    expect($images)->toBeArray()
        ->and($images)->toHaveCount(5)
        ->and($images[0])->toBeInstanceOf(ImageResult::class)
        ->and($images[0]->url)->toBeString()
        ->and($images[0]->altText)->toContain('Test keyword');
});

test('mock image provider respects limit parameter', function () {
    $provider = new MockImageProvider();
    
    $images = $provider->searchImages('test', 3);
    
    expect($images)->toHaveCount(3);
});

test('mock image provider returns custom images', function () {
    $customImages = [
        new ImageResult('https://example.com/1.jpg', 'Alt 1', null, 1.0, 800, 600),
        new ImageResult('https://example.com/2.jpg', 'Alt 2', null, 0.9, 1024, 768),
    ];
    
    $provider = new MockImageProvider($customImages);
    
    $images = $provider->searchImages('test');
    
    expect($images)->toHaveCount(2)
        ->and($images[0]->url)->toBe('https://example.com/1.jpg')
        ->and($images[0]->altText)->toContain('Test');
});

test('mock image provider is available by default', function () {
    $provider = new MockImageProvider();
    
    expect($provider->isAvailable())->toBeTrue();
});

test('mock image provider can be configured as unavailable', function () {
    $provider = new MockImageProvider(null, false);
    
    expect($provider->isAvailable())->toBeFalse();
});

test('mock image provider returns correct name', function () {
    $provider = new MockImageProvider();
    
    expect($provider->getName())->toBe('mock');
});

test('mock image provider can throw configured exception', function () {
    $provider = new MockImageProvider(null, true, ImageProviderException::class);
    
    expect(fn() => $provider->searchImages('test'))
        ->toThrow(ImageProviderException::class);
});

test('mock image provider updates alt text with keyword', function () {
    $provider = new MockImageProvider();
    
    $images = $provider->searchImages('Laravel');
    
    expect($images[0]->altText)->toStartWith('Laravel -');
});

test('mock image provider can be reconfigured via setters', function () {
    $provider = new MockImageProvider();
    
    $customImages = [
        new ImageResult('https://custom.com/img.jpg', 'Custom', null, 1.0, 500, 500),
    ];
    
    $provider->setMockImages($customImages)
        ->setAvailable(false);
    
    $images = $provider->searchImages('test');
    
    expect($images)->toHaveCount(1)
        ->and($images[0]->url)->toBe('https://custom.com/img.jpg')
        ->and($provider->isAvailable())->toBeFalse();
});
