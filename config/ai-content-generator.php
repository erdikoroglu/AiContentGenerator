<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider that will be used for
    | content generation. Supported: "openai", "moonshot", "google"
    |
    */
    'default_ai_provider' => env('AI_CONTENT_DEFAULT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for each AI provider including
    | API credentials, model selection, and request parameters.
    |
    */
    'ai_providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 4000),
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
            'timeout' => 60,
            'retry_attempts' => 3,
            'retry_delay' => 1000, // milliseconds
        ],
        'moonshot' => [
            'api_key' => env('MOONSHOT_API_KEY'),
            'model' => env('MOONSHOT_MODEL', 'moonshot-v1-8k'),
            'timeout' => 60,
            'retry_attempts' => 3,
            'retry_delay' => 1000,
        ],
        'google' => [
            'api_key' => env('GOOGLE_AI_API_KEY'),
            'model' => env('GOOGLE_AI_MODEL', 'gemini-pro'),
            'timeout' => 60,
            'retry_attempts' => 3,
            'retry_delay' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Provider Chain
    |--------------------------------------------------------------------------
    |
    | Define the order of fallback providers to use when the primary
    | provider fails or becomes unavailable.
    |
    */
    'fallback_providers' => ['openai', 'google', 'moonshot'],

    /*
    |--------------------------------------------------------------------------
    | Image Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Configure image search providers for automatic image fetching.
    | Supported: "pexels", "pixabay"
    |
    */
    'image_providers' => [
        'pexels' => [
            'api_key' => env('PEXELS_API_KEY'),
            'per_page' => 5,
            'timeout' => 30,
        ],
        'pixabay' => [
            'api_key' => env('PIXABAY_API_KEY'),
            'per_page' => 5,
            'timeout' => 30,
        ],
    ],

    'default_image_provider' => env('AI_CONTENT_DEFAULT_IMAGE_PROVIDER', 'pexels'),

    /*
    |--------------------------------------------------------------------------
    | Default Content Settings
    |--------------------------------------------------------------------------
    |
    | Default values for content generation requests.
    |
    */
    'defaults' => [
        'locale' => 'en_US',
        'locale_name' => 'English',
        'target_country' => 'US',
        'currency' => 'USD',
        'word_count_min' => 800,
        'word_count_max' => 1500,
        'intro_word_count' => 100,
        'conclusion_word_count' => 100,
        'main_content_word_count' => 600,
        'faq_min_count' => 3,
        'search_intent' => 'informational',
        'content_type' => 'concept',
        
        // Default author persona (used when no author ID is provided)
        'author_name' => env('AI_CONTENT_DEFAULT_AUTHOR_NAME', 'Content Writer'),
        'author_company' => env('AI_CONTENT_DEFAULT_AUTHOR_COMPANY'),
        'author_job_title' => env('AI_CONTENT_DEFAULT_AUTHOR_JOB_TITLE', 'Content Specialist'),
        'author_expertise_areas' => ['Content Writing', 'SEO', 'Digital Marketing'],
        'author_short_bio' => env('AI_CONTENT_DEFAULT_AUTHOR_BIO', 'Experienced content writer specializing in SEO-optimized articles and digital marketing content.'),
        'author_url' => env('AI_CONTENT_DEFAULT_AUTHOR_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Settings
    |--------------------------------------------------------------------------
    |
    | Configure SEO-related constraints and requirements.
    |
    */
    'seo' => [
        'keyword_density_min' => 0.5,
        'keyword_density_max' => 2.5,
        'meta_description_min' => 150,
        'meta_description_max' => 160,
        'excerpt_min' => 100,
        'excerpt_max' => 150,
        'title_min' => 50,
        'title_max' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    |
    | Configure content validation rules and behavior.
    |
    */
    'validation' => [
        'enabled_validators' => [
            'keyword_density',
            'adsense_compliance',
            'html_structure',
            'word_count',
            'contact_link',
        ],
        'strict_mode' => true,
        'max_regeneration_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for AI and image provider responses.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour in seconds
        'prefix' => 'ai_content_',
        'driver' => env('AI_CONTENT_CACHE_DRIVER', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior for the package.
    |
    */
    'logging' => [
        'enabled' => true,
        'level' => env('AI_CONTENT_LOG_LEVEL', 'info'),
        'channel' => env('AI_CONTENT_LOG_CHANNEL', 'stack'),
        'include_request_id' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Settings
    |--------------------------------------------------------------------------
    |
    | Configure mock mode for testing without real API calls.
    |
    */
    'testing' => [
        'mock_mode' => env('AI_CONTENT_MOCK_MODE', false),
        'mock_responses' => [
            'content' => '<h2>Sample Content</h2><p>This is mock content for testing.</p>',
            'images' => [
                [
                    'url' => 'https://example.com/image.jpg',
                    'alt' => 'Sample image',
                    'attribution' => null,
                    'relevance_score' => 0.9,
                    'width' => 1920,
                    'height' => 1080,
                ],
            ],
        ],
    ],
];
