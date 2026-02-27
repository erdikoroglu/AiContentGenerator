<?php

use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderAuthenticationException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderClientException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderRateLimitException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderTimeoutException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderUnavailableException;
use ErdiKoroglu\AIContentGenerator\Providers\AI\OpenAIProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;

beforeEach(function () {
    $this->config = [
        'api_key' => 'test-api-key',
        'model' => 'gpt-4',
        'max_tokens' => 4000,
        'temperature' => 0.7,
        'timeout' => 60,
        'retry_attempts' => 3,
        'retry_delay' => 100, // Use shorter delay for tests
    ];
});

afterEach(function () {
    m::close();
});

test('getName returns openai', function () {
    $provider = new OpenAIProvider($this->config);
    
    expect($provider->getName())->toBe('openai');
});

test('isAvailable returns true when api key is configured', function () {
    $provider = new OpenAIProvider($this->config);
    
    expect($provider->isAvailable())->toBeTrue();
});

test('isAvailable returns false when api key is missing', function () {
    $config = $this->config;
    $config['api_key'] = '';
    
    $provider = new OpenAIProvider($config);
    
    expect($provider->isAvailable())->toBeFalse();
});

test('generateContent returns content on successful response', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Generated content from OpenAI',
                    ],
                ],
            ],
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OpenAIProvider($this->config);
    
    // Use reflection to inject the mock client
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $result = $provider->generateContent('Test prompt');

    expect($result)->toBe('Generated content from OpenAI');
});

test('generateContent throws ProviderAuthenticationException on 401 error', function () {
    $mock = new MockHandler([
        new ClientException(
            'Unauthorized',
            new Request('POST', 'test'),
            new Response(401, [], json_encode(['error' => 'Invalid API key']))
        ),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OpenAIProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $provider->generateContent('Test prompt');
})->throws(ProviderAuthenticationException::class);

test('generateContent throws ProviderAuthenticationException on 403 error', function () {
    $mock = new MockHandler([
        new ClientException(
            'Forbidden',
            new Request('POST', 'test'),
            new Response(403, [], json_encode(['error' => 'Access denied']))
        ),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OpenAIProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $provider->generateContent('Test prompt');
})->throws(ProviderAuthenticationException::class);

test('generateContent throws ProviderRateLimitException on 429 error', function () {
    $mock = new MockHandler([
        new ClientException(
            'Rate limit exceeded',
            new Request('POST', 'test'),
            new Response(429, ['Retry-After' => '60'], json_encode(['error' => 'Rate limit']))
        ),
        new ClientException(
            'Rate limit exceeded',
            new Request('POST', 'test'),
            new Response(429, ['Retry-After' => '60'], json_encode(['error' => 'Rate limit']))
        ),
        new ClientException(
            'Rate limit exceeded',
            new Request('POST', 'test'),
            new Response(429, ['Retry-After' => '60'], json_encode(['error' => 'Rate limit']))
        ),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OpenAIProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $provider->generateContent('Test prompt');
})->throws(ProviderRateLimitException::class);

test('generateContent retries on rate limit and succeeds', function () {
    $mock = new MockHandler([
        new ClientException(
            'Rate limit exceeded',
            new Request('POST', 'test'),
            new Response(429, [], json_encode(['error' => 'Rate limit']))
        ),
        new Response(200, [], json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Success after retry',
                    ],
                ],
            ],
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OpenAIProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $result = $provider->generateContent('Test prompt');

    expect($result)->toBe('Success after retry');
});

test('generateContent throws ProviderUnavailableException on 5xx error after retries', function () {
    $mock = new MockHandler([
        new ServerException(
            'Server error',
            new Request('POST', 'test'),
            new Response(503, [], json_encode(['error' => 'Service unavailable']))
        ),
        new ServerException(
            'Server error',
            new Request('POST', 'test'),
            new Response(503, [], json_encode(['error' => 'Service unavailable']))
        ),
        new ServerException(
            'Server error',
            new Request('POST', 'test'),
            new Response(503, [], json_encode(['error' => 'Service unavailable']))
        ),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OpenAIProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $provider->generateContent('Test prompt');
})->throws(ProviderUnavailableException::class);

test('generateContent throws ProviderTimeoutException on connection timeout', function () {
    $mock = new MockHandler([
        new ConnectException(
            'Connection timeout',
            new Request('POST', 'test')
        ),
        new ConnectException(
            'Connection timeout',
            new Request('POST', 'test')
        ),
        new ConnectException(
            'Connection timeout',
            new Request('POST', 'test')
        ),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OpenAIProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $provider->generateContent('Test prompt');
})->throws(ProviderTimeoutException::class);

test('generateContent throws ProviderClientException on 400 error without retry', function () {
    // 400 errors should not retry, so only one response is needed
    $mock = new MockHandler([
        new ClientException(
            'Bad request',
            new Request('POST', 'test'),
            new Response(400, [], json_encode(['error' => 'Invalid request']))
        ),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OpenAIProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $provider->generateContent('Test prompt');
})->throws(ProviderClientException::class);

test('generateContent uses custom options when provided', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Custom options content',
                    ],
                ],
            ],
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $provider = new OpenAIProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($provider, $client);

    $result = $provider->generateContent('Test prompt', [
        'model' => 'gpt-3.5-turbo',
        'temperature' => 0.5,
        'max_tokens' => 2000,
    ]);

    expect($result)->toBe('Custom options content');
});

test('exponential backoff increases delay correctly', function () {
    // This test verifies the retry delay calculation
    $provider = new OpenAIProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('calculateRetryDelay');
    $method->setAccessible(true);

    // Base delay is 100ms for tests
    $baseDelay = 100;
    
    // Attempt 1: 100ms * 2^0 = 100ms
    expect($method->invoke($provider, 1, $baseDelay))->toBe(100);
    
    // Attempt 2: 100ms * 2^1 = 200ms
    expect($method->invoke($provider, 2, $baseDelay))->toBe(200);
    
    // Attempt 3: 100ms * 2^2 = 400ms
    expect($method->invoke($provider, 3, $baseDelay))->toBe(400);
    
    // Attempt 4: 100ms * 2^3 = 800ms
    expect($method->invoke($provider, 4, $baseDelay))->toBe(800);
});

test('exponential backoff caps at maximum delay', function () {
    $provider = new OpenAIProvider($this->config);
    
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('calculateRetryDelay');
    $method->setAccessible(true);

    // With base delay of 10000ms, attempt 5 would be 160000ms
    // But it should cap at 30000ms
    $result = $method->invoke($provider, 5, 10000);
    
    expect($result)->toBe(30000);
});
