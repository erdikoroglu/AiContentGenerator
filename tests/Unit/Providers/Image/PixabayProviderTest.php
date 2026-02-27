<?php

use ErdiKoroglu\AIContentGenerator\DTOs\ImageResult;
use ErdiKoroglu\AIContentGenerator\Providers\Image\PixabayProvider;
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
        'hits' => [
            [
                'id' => 1,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'johndoe',
                'pageURL' => 'https://pixabay.com/photos/laravel-1/',
                'tags' => 'Laravel, framework, PHP',
                'largeImageURL' => 'https://example.com/image-large.jpg',
            ],
            [
                'id' => 2,
                'imageWidth' => 1280,
                'imageHeight' => 720,
                'user' => 'janesmith',
                'pageURL' => 'https://pixabay.com/photos/php-2/',
                'tags' => 'PHP, Laravel, development',
                'largeImageURL' => 'https://example.com/image2-large.jpg',
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
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
        ->and($results[0]->attribution)->toContain('johndoe')
        ->and($results[1])->toBeInstanceOf(ImageResult::class);
});

test('calculates relevance scores correctly based on tags', function () {
    $mockResponse = [
        'hits' => [
            [
                'id' => 1,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'test',
                'tags' => 'Laravel framework', // Exact match with keyword
                'largeImageURL' => 'https://example.com/1.jpg',
            ],
            [
                'id' => 2,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'test',
                'tags' => 'PHP, Laravel, framework, tutorial', // Contains keyword words separately
                'largeImageURL' => 'https://example.com/2.jpg',
            ],
            [
                'id' => 3,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'test',
                'tags' => 'random, image, unrelated', // No match
                'largeImageURL' => 'https://example.com/3.jpg',
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel framework', 5);

    // Results should be sorted by relevance score
    expect($results[0]->relevanceScore)->toBeGreaterThan($results[1]->relevanceScore)
        ->and($results[1]->relevanceScore)->toBeGreaterThan($results[2]->relevanceScore)
        ->and($results[0]->relevanceScore)->toBeGreaterThanOrEqual(0.9) // Exact or contains match
        ->and($results[1]->relevanceScore)->toBeGreaterThanOrEqual(0.7) // Contains keyword words
        ->and($results[2]->relevanceScore)->toBeLessThanOrEqual(0.2); // No match
});

test('calculates relevance score for exact tag match', function () {
    $mockResponse = [
        'hits' => [
            [
                'id' => 1,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'test',
                'tags' => 'Laravel, PHP, framework', // Laravel is exact tag match
                'largeImageURL' => 'https://example.com/1.jpg',
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results[0]->relevanceScore)->toBe(1.0); // Exact tag match
});

test('generates alt text from tags and keyword', function () {
    $mockResponse = [
        'hits' => [
            [
                'id' => 1,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'test',
                'tags' => 'Laravel, PHP, framework',
                'largeImageURL' => 'https://example.com/1.jpg',
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results[0]->altText)->toContain('Laravel');
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

    $provider = new PixabayProvider($this->config);
    
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

    $provider = new PixabayProvider($this->config);
    
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

    $provider = new PixabayProvider($this->config);
    
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

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()->toBeEmpty();
});

test('handles empty hits array', function () {
    $mockResponse = [
        'hits' => [],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()->toBeEmpty();
});

test('handles missing hits key in response', function () {
    $mockResponse = [
        'total' => 0,
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()->toBeEmpty();
});

test('skips invalid hit entries', function () {
    $mockResponse = [
        'hits' => [
            [
                'id' => 1,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'johndoe',
                'tags' => 'valid image',
                'largeImageURL' => 'https://example.com/valid.jpg',
            ],
            [
                'id' => 2,
                // Missing 'largeImageURL' - invalid
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'janedoe',
                'tags' => 'invalid image',
            ],
            [
                'id' => 3,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'bobsmith',
                'tags' => 'another valid image',
                'largeImageURL' => 'https://example.com/valid2.jpg',
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toBeArray()
        ->toHaveCount(2); // Only 2 valid images
});

test('isAvailable returns true when API key is configured', function () {
    $provider = new PixabayProvider($this->config);

    expect($provider->isAvailable())->toBeTrue();
});

test('isAvailable returns false when API key is missing', function () {
    $config = [
        'api_key' => '',
        'per_page' => 5,
        'timeout' => 30,
    ];

    $provider = new PixabayProvider($config);

    expect($provider->isAvailable())->toBeFalse();
});

test('getName returns correct provider name', function () {
    $provider = new PixabayProvider($this->config);

    expect($provider->getName())->toBe('pixabay');
});

test('builds attribution with user and page URL', function () {
    $mockResponse = [
        'hits' => [
            [
                'id' => 1,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'johndoe',
                'pageURL' => 'https://pixabay.com/photos/test-1/',
                'tags' => 'test image',
                'largeImageURL' => 'https://example.com/image.jpg',
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('test', 5);

    expect($results[0]->attribution)
        ->toContain('johndoe')
        ->toContain('Pixabay')
        ->toContain('https://pixabay.com/photos/test-1/');
});

test('builds attribution without page URL', function () {
    $mockResponse = [
        'hits' => [
            [
                'id' => 1,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'johndoe',
                'tags' => 'test image',
                'largeImageURL' => 'https://example.com/image.jpg',
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('test', 5);

    expect($results[0]->attribution)
        ->toContain('johndoe')
        ->toContain('Pixabay')
        ->not->toContain('http');
});

test('handles empty tags gracefully', function () {
    $mockResponse = [
        'hits' => [
            [
                'id' => 1,
                'imageWidth' => 1920,
                'imageHeight' => 1080,
                'user' => 'test',
                'tags' => '',
                'largeImageURL' => 'https://example.com/1.jpg',
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $results = $provider->searchImages('Laravel', 5);

    expect($results)->toHaveCount(1)
        ->and($results[0]->altText)->toBe('Laravel'); // Falls back to keyword
});

test('sends API key in query parameter', function () {
    $mockResponse = [
        'hits' => [],
    ];

    $requestHistory = [];
    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push(\GuzzleHttp\Middleware::history($requestHistory));
    $client = new Client(['handler' => $handlerStack]);

    $provider = new PixabayProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $provider->searchImages('Laravel', 5);

    expect($requestHistory)->toHaveCount(1);
    
    $request = $requestHistory[0]['request'];
    $query = $request->getUri()->getQuery();
    
    expect($query)->toContain('key=test-api-key')
        ->toContain('q=Laravel')
        ->toContain('per_page=5');
});
