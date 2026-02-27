<?php

namespace ErdiKoroglu\AIContentGenerator\Tests;

use ErdiKoroglu\AIContentGenerator\AIContentGeneratorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AIContentGeneratorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default configuration
        config()->set('ai-content-generator.ai_providers.openai', [
            'api_key' => 'test-api-key',
            'model' => 'gpt-4',
            'max_tokens' => 4000,
            'temperature' => 0.7,
            'timeout' => 60,
            'retry_attempts' => 3,
            'retry_delay' => 1000,
        ]);

        config()->set('ai-content-generator.logging.enabled', false);
    }
}
