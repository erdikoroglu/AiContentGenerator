<?php

declare(strict_types=1);

use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderAuthenticationException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderClientException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderRateLimitException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderTimeoutException;
use ErdiKoroglu\AIContentGenerator\Exceptions\ProviderUnavailableException;
use ErdiKoroglu\AIContentGenerator\Providers\AI\GoogleAIProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Config::set('ai-content-generator.logging.enabled', false);
    
    $this->config = [
        'api_key' => 'test-google-api-key',
        'model' => 'gemini-pro',
        'timeout' => 60,
        'retry_attempts' => 3,
        'retry_delay' => 1000,
        'temperature' => 0.7,
    ];
});

describe('GoogleAIProvider', function () {
    test('implements AIProviderInterface', function () {
        $provider = new GoogleAIProvider($this->config);
        
        expect($provider)->toBeInstanceOf(\ErdiKoroglu\AIContentGenerator\Providers\AI\AIProviderInterface::class);
    });

    test('getName returns google', function () {
        $provider = new GoogleAIProvider($this->config);
        
        expect($provider->getName())->toBe('google');
    });

    test('isAvailable returns true when API key is configured', function () {
        $provider = new GoogleAIProvider($this->config);
        
        expect($provider->isAvailable())->toBeTrue();
    });

    test('isAvailable returns false when API key is missing', function () {
        $config = $this->config;
        $config['api_key'] = '';
        
        $provider = new GoogleAIProvider($config);
        
        expect($provider->isAvailable())->toBeFalse();
    });
});

describe('generateContent', function () {
    test('successfully generates content with valid response', function () {
        $mockResponse = new Response(200, [], json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Generated content from Google AI'],
                        ],
                    ],
                ],
            ],
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $content = $provider->generateContent('Test prompt');

        expect($content)->toBe('Generated content from Google AI');
    });

    test('throws ProviderUnavailableException when response format is invalid', function () {
        $mockResponse = new Response(200, [], json_encode([
            'invalid' => 'response',
        ]));

        // Provider will retry on ProviderUnavailableException, so we need 3 responses
        $mock = new MockHandler([$mockResponse, $mockResponse, $mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        try {
            $provider->generateContent('Test prompt');
            expect(false)->toBeTrue('Expected ProviderUnavailableException to be thrown');
        } catch (ProviderUnavailableException $e) {
            // Should throw ProviderUnavailableException
            expect($e)->toBeInstanceOf(ProviderUnavailableException::class);
        }
    });

    test('throws ProviderAuthenticationException on 401 error', function () {
        $request = new Request('POST', 'test');
        $response = new Response(401, [], json_encode(['error' => 'Unauthorized']));
        $mockException = new ClientException('Unauthorized', $request, $response);

        $mock = new MockHandler([$mockException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $provider->generateContent('Test prompt');
    })->throws(ProviderAuthenticationException::class);

    test('throws ProviderAuthenticationException on 403 error', function () {
        $request = new Request('POST', 'test');
        $response = new Response(403, [], json_encode(['error' => 'Forbidden']));
        $mockException = new ClientException('Forbidden', $request, $response);

        $mock = new MockHandler([$mockException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $provider->generateContent('Test prompt');
    })->throws(ProviderAuthenticationException::class);

    test('throws ProviderRateLimitException on 429 error', function () {
        $request = new Request('POST', 'test');
        $response = new Response(429, [], json_encode(['error' => 'Rate limit exceeded']));
        $mockException = new ClientException('Rate limit', $request, $response);

        $mock = new MockHandler([$mockException, $mockException, $mockException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $provider->generateContent('Test prompt');
    })->throws(ProviderRateLimitException::class);

    test('throws ProviderClientException on 400 error', function () {
        $request = new Request('POST', 'test');
        $response = new Response(400, [], json_encode(['error' => 'Bad request']));
        $mockException = new ClientException('Bad request', $request, $response);

        $mock = new MockHandler([$mockException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $provider->generateContent('Test prompt');
    })->throws(ProviderClientException::class);

    test('throws ProviderClientException on 404 error', function () {
        $request = new Request('POST', 'test');
        $response = new Response(404, [], json_encode(['error' => 'Not found']));
        $mockException = new ClientException('Not found', $request, $response);

        $mock = new MockHandler([$mockException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $provider->generateContent('Test prompt');
    })->throws(ProviderClientException::class);

    test('throws ProviderUnavailableException on 5xx error', function () {
        $request = new Request('POST', 'test');
        $response = new Response(500, [], json_encode(['error' => 'Internal server error']));
        $mockException = new ServerException('Server error', $request, $response);

        $mock = new MockHandler([$mockException, $mockException, $mockException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $provider->generateContent('Test prompt');
    })->throws(ProviderUnavailableException::class);

    test('throws ProviderTimeoutException on connection timeout', function () {
        $request = new Request('POST', 'test');
        $mockException = new ConnectException('Connection timeout', $request);

        $mock = new MockHandler([$mockException, $mockException, $mockException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $provider->generateContent('Test prompt');
    })->throws(ProviderTimeoutException::class);

    test('retries on rate limit error with exponential backoff', function () {
        $request = new Request('POST', 'test');
        $rateLimitResponse = new Response(429, [], json_encode(['error' => 'Rate limit']));
        $rateLimitException = new ClientException('Rate limit', $request, $rateLimitResponse);
        
        $successResponse = new Response(200, [], json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Success after retry'],
                        ],
                    ],
                ],
            ],
        ]));

        $mock = new MockHandler([$rateLimitException, $successResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $content = $provider->generateContent('Test prompt');

        expect($content)->toBe('Success after retry');
    });

    test('retries on server error with exponential backoff', function () {
        $request = new Request('POST', 'test');
        $serverErrorResponse = new Response(503, [], json_encode(['error' => 'Service unavailable']));
        $serverErrorException = new ServerException('Service unavailable', $request, $serverErrorResponse);
        
        $successResponse = new Response(200, [], json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Success after retry'],
                        ],
                    ],
                ],
            ],
        ]));

        $mock = new MockHandler([$serverErrorException, $successResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $content = $provider->generateContent('Test prompt');

        expect($content)->toBe('Success after retry');
    });

    test('does not retry on authentication error', function () {
        $request = new Request('POST', 'test');
        $authResponse = new Response(401, [], json_encode(['error' => 'Unauthorized']));
        $authException = new ClientException('Unauthorized', $request, $authResponse);
        
        // Only one exception - should not retry
        $mock = new MockHandler([$authException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $provider->generateContent('Test prompt');
    })->throws(ProviderAuthenticationException::class);

    test('uses custom options when provided', function () {
        $mockResponse = new Response(200, [], json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Generated content'],
                        ],
                    ],
                ],
            ],
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        $content = $provider->generateContent('Test prompt', [
            'model' => 'gemini-pro-vision',
            'temperature' => 0.9,
            'max_tokens' => 2000,
        ]);

        expect($content)->toBe('Generated content');
    });
});

describe('validateCredentials', function () {
    test('returns true when credentials are valid', function () {
        $mockResponse = new Response(200, [], json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'test'],
                        ],
                    ],
                ],
            ],
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        expect($provider->validateCredentials())->toBeTrue();
    });

    test('returns false when API key is missing', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('Google AI API key is not configured');

        $config = $this->config;
        $config['api_key'] = '';
        
        $provider = new GoogleAIProvider($config);

        expect($provider->validateCredentials())->toBeFalse();
    });

    test('returns false when credentials are invalid (401)', function () {
        Log::shouldReceive('error')
            ->once()
            ->with('Google AI credentials validation failed', Mockery::any());

        $request = new Request('POST', 'test');
        $response = new Response(401, [], json_encode(['error' => 'Unauthorized']));
        $mockException = new ClientException('Unauthorized', $request, $response);

        $mock = new MockHandler([$mockException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        expect($provider->validateCredentials())->toBeFalse();
    });

    test('returns false when credentials are invalid (403)', function () {
        Log::shouldReceive('error')
            ->once()
            ->with('Google AI credentials validation failed', Mockery::any());

        $request = new Request('POST', 'test');
        $response = new Response(403, [], json_encode(['error' => 'Forbidden']));
        $mockException = new ClientException('Forbidden', $request, $response);

        $mock = new MockHandler([$mockException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        expect($provider->validateCredentials())->toBeFalse();
    });

    test('returns true on other client errors (not auth related)', function () {
        $request = new Request('POST', 'test');
        $response = new Response(400, [], json_encode(['error' => 'Bad request']));
        $mockException = new ClientException('Bad request', $request, $response);

        $mock = new MockHandler([$mockException]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        expect($provider->validateCredentials())->toBeTrue();
    });

    test('returns false on general exception', function () {
        Log::shouldReceive('error')
            ->once()
            ->with('Google AI credentials validation error', Mockery::any());

        $mock = new MockHandler([new \Exception('Network error')]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new GoogleAIProvider($this->config);
        $reflection = new ReflectionClass($provider);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($provider, $client);

        expect($provider->validateCredentials())->toBeFalse();
    });
});
