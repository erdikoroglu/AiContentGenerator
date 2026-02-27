<?php

use ErdiKoroglu\AIContentGenerator\DTOs\ImageResult;
use ErdiKoroglu\AIContentGenerator\Providers\Image\PexelsProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->config = [
        'api_key' => 'test-api-key',
        'per_page' => 5,
        'timeout' => 30,
    ];
});

test('returns image results from successful API response', function () {
    $mockResponse = [
        'photos' => [
            [
                'id' => 1,
                'width' => 1920,
                'height' => 1080,
                'photographer' => 'John Doe',
                'photographer_url' => 'https://pexels.com/@johndoe',
                'alt' => 'Beautiful Laravel framework code',
                'src' => [
                    'large' => 'https://example.com/image-large.jpg',
                    'original' => 'https://example.com/image-original.jpg',
                ],
            ],
            [
                'id' => 2,
                'width' => 1280,
                'height' => 720,
                'photographer' => 'Jane Smith',
                'photographer_url' => 'https://pexels.com/@janesmith',
                'alt' => 'PHP Laravel development',
                'src' => [
                    'large' => 'https://example.com/image2-large.jpg',
                ],
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    // Use reflection to inject mock client
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()
        ->toHaveCount(2)
        ->and($results[0])->toBeInstanceOf(ImageResult::class)
        ->and($results[0]->url)->toBe('https://example.com/image-large.jpg')
        ->and($results[0]->width)->toBe(1920)
        ->and($results[0]->height)->toBe(1080)
        ->and($results[0]->attribution)->toContain('John Doe')
        ->and($results[1])->toBeInstanceOf(ImageResult::class);
});

test('calculates relevance scores correctly', function () {
    $mockResponse = [
        'photos' => [
            [
                'id' => 1,
                'width' => 1920,
                'height' => 1080,
                'photographer' => 'Test',
                'alt' => 'Laravel framework', // Exact match with keyword
                'src' => ['large' => 'https://example.com/1.jpg'],
            ],
            [
                'id' => 2,
                'width' => 1920,
                'height' => 1080,
                'photographer' => 'Test',
                'alt' => 'PHP code with Laravel framework tutorial', // Contains keyword phrase
                'src' => ['large' => 'https://example.com/2.jpg'],
            ],
            [
                'id' => 3,
                'width' => 1920,
                'height' => 1080,
                'photographer' => 'Test',
                'alt' => 'Random image', // No match
                'src' => ['large' => 'https://example.com/3.jpg'],
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel framework', 5);

    // Results should be sorted by relevance score
    expect($results[0]->relevanceScore)->toBeGreaterThan($results[1]->relevanceScore)
        ->and($results[1]->relevanceScore)->toBeGreaterThan($results[2]->relevanceScore)
        ->and($results[0]->relevanceScore)->toBe(1.0) // Exact match
        ->and($results[1]->relevanceScore)->toBeGreaterThanOrEqual(0.8) // Contains keyword phrase
        ->and($results[2]->relevanceScore)->toBeLessThanOrEqual(0.2); // No match
});

test('returns empty array on rate limit error (403)', function () {
    $mock = new MockHandler([
        new ClientException(
            'Rate limit exceeded',
            new Request('GET', 'test'),
            new Response(403, [], 'Rate limit exceeded')
        ),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()->toBeEmpty();
});

test('returns empty array on rate limit error (429)', function () {
    $mock = new MockHandler([
        new ClientException(
            'Too many requests',
            new Request('GET', 'test'),
            new Response(429, [], 'Too many requests')
        ),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()->toBeEmpty();
});

test('returns empty array on server error', function () {
    $mock = new MockHandler([
        new ServerException(
            'Server error',
            new Request('GET', 'test'),
            new Response(500, [], 'Internal server error')
        ),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()->toBeEmpty();
});

test('returns empty array on connection timeout', function () {
    $mock = new MockHandler([
        new ConnectException(
            'Connection timeout',
            new Request('GET', 'test')
        ),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()->toBeEmpty();
});

test('handles empty photos array', function () {
    $mockResponse = [
        'photos' => [],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()->toBeEmpty();
});

test('handles missing photos key in response', function () {
    $mockResponse = [
        'total_results' => 0,
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()->toBeEmpty();
});

test('skips invalid photo entries', function () {
    $mockResponse = [
        'photos' => [
            [
                'id' => 1,
                'width' => 1920,
                'height' => 1080,
                'photographer' => 'John Doe',
                'alt' => 'Valid image',
                'src' => [
                    'large' => 'https://example.com/valid.jpg',
                ],
            ],
            [
                'id' => 2,
                // Missing 'src' - invalid
                'width' => 1920,
                'height' => 1080,
                'photographer' => 'Jane Doe',
                'alt' => 'Invalid image',
            ],
            [
                'id' => 3,
                'width' => 1920,
                'height' => 1080,
                'photographer' => 'Bob Smith',
                'alt' => 'Another valid image',
                'src' => [
                    'original' => 'https://example.com/valid2.jpg',
                ],
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()
        ->toHaveCount(2); // Only 2 valid images
});

test('isAvailable returns true when API key is configured', function () {
    $provider = new PexelsProvider($this->config);

    expect($provider->isAvailable())->toBeTrue();
});

test('isAvailable returns false when API key is missing', function () {
    $config = [
        'api_key' => '',
        'per_page' => 5,
        'timeout' => 30,
    ];

    $provider = new PexelsProvider($config);

    expect($provider->isAvailable())->toBeFalse();
});

test('getName returns correct provider name', function () {
    $provider = new PexelsProvider($this->config);

    expect($provider->getName())->toBe('pexels');
});

test('builds attribution with photographer and URL', function () {
    $mockResponse = [
        'photos' => [
            [
                'id' => 1,
                'width' => 1920,
                'height' => 1080,
                'photographer' => 'John Doe',
                'photographer_url' => 'https://pexels.com/@johndoe',
                'alt' => 'Test image',
                'src' => [
                    'large' => 'https://example.com/image.jpg',
                ],
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('test', 5);

    expect($results[0]->attribution)
        ->toContain('John Doe')
        ->toContain('Pexels')
        ->toContain('https://pexels.com/@johndoe');
});

test('builds attribution without photographer URL', function () {
    $mockResponse = [
        'photos' => [
            [
                'id' => 1,
                'width' => 1920,
                'height' => 1080,
                'photographer' => 'John Doe',
                'alt' => 'Test image',
                'src' => [
                    'large' => 'https://example.com/image.jpg',
                ],
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PexelsProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('test', 5);

    expect($results[0]->attribution)
        ->toContain('John Doe')
        ->toContain('Pexels')
        ->not->toContain('http');
});
