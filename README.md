# Laravel AI Content Generator

[![Latest Version](https://img.shields.io/packagist/v/erdikoroglu/laravel-ai-content-generator.svg?style=flat-square)](https://packagist.org/packages/erdikoroglu/laravel-ai-content-generator)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/erdikoroglu/laravel-ai-content-generator.svg?style=flat-square)](https://packagist.org/packages/erdikoroglu/laravel-ai-content-generator)

Laravel AI Content Generator is a comprehensive Composer package for Laravel 10+ applications that provides multi-provider AI integration for generating SEO-optimized, E-E-A-T compliant blog content with automatic image integration, content validation, and caching.

## Features

- 🤖 **Multi-Provider AI Support**: OpenAI (GPT-4/3.5), MoonShot AI, and Google AI (Gemini)
- 🔄 **Automatic Fallback**: Seamless provider switching on failures with exponential backoff
- 🖼️ **Image Integration**: Pexels and Pixabay support for automatic image fetching and selection
- 🎯 **SEO Optimization**: Keyword density control (0.5%-2.5%), meta descriptions, search intent matching
- ✅ **E-E-A-T Compliance**: Author persona management for expertise, experience, authoritativeness, and trustworthiness
- 🌍 **Multi-Language Support**: Locale-aware content generation with cultural adaptation
- 🛡️ **Content Validation**: AdSense compliance, HTML structure, word count, contact link validation
- ⚡ **Performance**: Built-in Laravel cache integration with configurable TTL
- 📊 **Comprehensive Logging**: Full request/response tracking with unique request IDs
- 🎨 **Content Types**: Support for how-to, concept, and news articles
- ❓ **FAQ Generation**: Automatic FAQ section generation with schema markup compatibility
- 🧪 **Testing Support**: Mock providers for testing without real API calls

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Composer

## Installation

### Step 1: Install via Composer

```bash
composer require erdikoroglu/laravel-ai-content-generator
```

The service provider will be automatically registered.

### Step 2: Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=ai-content-generator-config
```

This creates `config/ai-content-generator.php` with all available options.

### Step 3: Publish and Run Migrations

Publish migration files:

```bash
php artisan vendor:publish --tag=ai-content-generator-migrations
```

Run migrations to create required tables:

```bash
php artisan migrate
```

This creates:
- `author_personas` - Store author profiles with expertise areas
- `content_metadata` - Track generated content metadata

## Configuration

### Environment Variables

Add your API credentials to `.env`:

```env
# Default AI Provider (openai, moonshot, or google)
AI_CONTENT_DEFAULT_PROVIDER=openai

# AI Provider API Keys
OPENAI_API_KEY=your-openai-api-key
OPENAI_MODEL=gpt-4
MOONSHOT_API_KEY=your-moonshot-api-key
GOOGLE_AI_API_KEY=your-google-ai-api-key

# Image Provider API Keys
PEXELS_API_KEY=your-pexels-api-key
PIXABAY_API_KEY=your-pixabay-api-key
AI_CONTENT_DEFAULT_IMAGE_PROVIDER=pexels

# Cache Configuration
AI_CONTENT_CACHE_DRIVER=redis
AI_CONTENT_CACHE_TTL=3600

# Logging
AI_CONTENT_LOG_LEVEL=info
AI_CONTENT_LOG_CHANNEL=stack
```

### AI Providers

The package supports three AI providers:

#### OpenAI (GPT-4/GPT-3.5)
```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4'),
    'max_tokens' => 4000,
    'temperature' => 0.7,
]
```

#### MoonShot AI
```php
'moonshot' => [
    'api_key' => env('MOONSHOT_API_KEY'),
    'model' => 'moonshot-v1-8k',
]
```

#### Google AI (Gemini)
```php
'google' => [
    'api_key' => env('GOOGLE_AI_API_KEY'),
    'model' => 'gemini-pro',
]
```

### Fallback Configuration

Configure provider fallback order:

```php
'fallback_providers' => ['openai', 'google', 'moonshot'],
```

### Validation Settings

Enable/disable specific validators:

```php
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
```

## Usage

### Basic Usage

```php
use ErdiKoroglu\AIContentGenerator\Facades\AIContentGenerator;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;
use ErdiKoroglu\AIContentGenerator\DTOs\LocaleConfiguration;
use ErdiKoroglu\AIContentGenerator\Models\AuthorPersona;

// Create or retrieve an author persona
$author = AuthorPersona::create([
    'author_name' => 'John Doe',
    'author_company' => 'Tech Solutions Inc.',
    'author_job_title' => 'Senior Software Engineer',
    'author_expertise_areas' => ['Laravel', 'PHP', 'Web Development'],
    'author_short_bio' => 'John is a senior software engineer with 10+ years of experience in web development.',
    'author_url' => 'https://example.com/about',
]);

// Create locale configuration
$locale = new LocaleConfiguration(
    locale: 'en_US',
    localeName: 'English',
    targetCountry: 'United States',
    currency: 'USD'
);

// Create content request
$request = new ContentRequest(
    focusKeyword: 'Laravel best practices',
    relatedKeywords: ['PHP framework', 'web development', 'MVC pattern'],
    searchIntent: 'informational',
    contentType: 'how-to',
    locale: $locale,
    author: $author,
    wordCountMin: 800,
    wordCountMax: 1500,
    introWordCount: 100,
    conclusionWordCount: 100,
    mainContentWordCount: 600,
    faqMinCount: 5,
    contactUrl: 'https://example.com/contact'
);

// Generate content
$response = AIContentGenerator::generate($request);

// Access generated content
echo $response->title;              // Article title
echo $response->metaDescription;    // SEO meta description
echo $response->excerpt;            // Article excerpt
echo $response->content;            // Full HTML content
print_r($response->faqs);           // FAQ array
print_r($response->images);         // Image results
echo $response->wordCount;          // Actual word count
echo $response->generatedAt;        // Generation timestamp

// Export as JSON
$json = $response->toJson();
```

### Using Different AI Providers

```php
// Use a specific provider for one request
$response = AIContentGenerator::setAIProvider('google')
    ->generate($request);

// Or specify in the request
$request = new ContentRequest(
    // ... other parameters
    aiProvider: 'moonshot'
);
```

### Custom Author Personas

```php
// Create a technical writer persona
$techWriter = AuthorPersona::create([
    'author_name' => 'Jane Smith',
    'author_company' => 'DevMedia',
    'author_job_title' => 'Technical Content Writer',
    'author_expertise_areas' => [
        'Technical Writing',
        'API Documentation',
        'Developer Education'
    ],
    'author_short_bio' => 'Jane specializes in creating clear, comprehensive technical documentation for developers.',
    'author_url' => 'https://devmedia.com/authors/jane-smith',
]);

// Use in content generation
$request = new ContentRequest(
    // ... other parameters
    author: $techWriter
);
```

### Multi-Language Content

```php
// Generate Turkish content
$turkishLocale = new LocaleConfiguration(
    locale: 'tr_TR',
    localeName: 'Türkçe',
    targetCountry: 'Turkey',
    currency: 'TRY'
);

$request = new ContentRequest(
    focusKeyword: 'Laravel en iyi uygulamalar',
    relatedKeywords: ['PHP framework', 'web geliştirme'],
    // ... other parameters
    locale: $turkishLocale
);

// Generate German content
$germanLocale = new LocaleConfiguration(
    locale: 'de_DE',
    localeName: 'Deutsch',
    targetCountry: 'Germany',
    currency: 'EUR'
);
```

### Content Types

#### How-To Articles
```php
$request = new ContentRequest(
    focusKeyword: 'How to deploy Laravel application',
    contentType: 'how-to',
    searchIntent: 'informational',
    // ... other parameters
);
```

#### Concept Explanations
```php
$request = new ContentRequest(
    focusKeyword: 'What is dependency injection',
    contentType: 'concept',
    searchIntent: 'informational',
    // ... other parameters
);
```

#### News Articles
```php
$request = new ContentRequest(
    focusKeyword: 'Laravel 11 new features',
    contentType: 'news',
    searchIntent: 'informational',
    // ... other parameters
);
```

### Using the Artisan Command

Generate content from the command line:

```bash
# Basic usage
php artisan ai-content:generate \
    --focus-keyword="Laravel best practices" \
    --author-id=1 \
    --contact-url="https://example.com/contact"

# With all options
php artisan ai-content:generate \
    --focus-keyword="Laravel testing guide" \
    --related-keywords="PHPUnit,Pest,TDD" \
    --search-intent="informational" \
    --content-type="how-to" \
    --locale="en_US" \
    --author-id=1 \
    --word-count-min=1000 \
    --word-count-max=2000 \
    --faq-count=5 \
    --contact-url="https://example.com/contact" \
    --ai-provider="openai" \
    --image-provider="pexels" \
    --output="content.json"
```

### Cache Management

```php
use ErdiKoroglu\AIContentGenerator\Services\ContentGeneratorService;

$generator = app(ContentGeneratorService::class);

// Clear cache for specific request
$generator->clearCache($request);

// Clear all content generation cache
$generator->clearAllCache();
```

## API Documentation

### ContentRequest DTO

| Property | Type | Description |
|----------|------|-------------|
| `focusKeyword` | string | Main keyword for SEO optimization |
| `relatedKeywords` | array | Related keywords to incorporate |
| `searchIntent` | string | informational, navigational, transactional, commercial |
| `contentType` | string | how-to, concept, news |
| `locale` | LocaleConfiguration | Language and region settings |
| `author` | AuthorPersona | Author profile with expertise |
| `wordCountMin` | int | Minimum word count (default: 800) |
| `wordCountMax` | int | Maximum word count (default: 1500) |
| `introWordCount` | int | Introduction word count (default: 100) |
| `conclusionWordCount` | int | Conclusion word count (default: 100) |
| `mainContentWordCount` | int | Main content word count (default: 600) |
| `faqMinCount` | int | Minimum FAQ items (default: 3) |
| `contactUrl` | string | Contact URL to include in content |
| `aiProvider` | string\|null | Override default AI provider |
| `imageProvider` | string\|null | Override default image provider |

### ContentResponse DTO

| Property | Type | Description |
|----------|------|-------------|
| `title` | string | Generated article title (50-60 chars) |
| `metaDescription` | string | SEO meta description (150-160 chars) |
| `excerpt` | string | Article excerpt (100-150 words) |
| `focusKeyword` | string | Focus keyword used |
| `content` | string | Full HTML content |
| `faqs` | array | FAQ items with question/answer pairs |
| `images` | array | ImageResult objects |
| `wordCount` | int | Actual word count |
| `generatedAt` | Carbon | Generation timestamp |

### LocaleConfiguration DTO

| Property | Type | Description |
|----------|------|-------------|
| `locale` | string | Locale code (e.g., en_US, tr_TR) |
| `localeName` | string | Locale display name |
| `targetCountry` | string | Target country |
| `currency` | string | Currency code |

### ImageResult DTO

| Property | Type | Description |
|----------|------|-------------|
| `url` | string | Image URL |
| `altText` | string | Suggested alt text |
| `attribution` | string\|null | Attribution information |
| `relevanceScore` | float | Relevance score (0-1) |
| `width` | int | Image width in pixels |
| `height` | int | Image height in pixels |

### Main Service Methods

#### ContentGeneratorService

```php
// Generate content
public function generate(ContentRequest $request): ContentResponse

// Set AI provider override
public function setAIProvider(string $provider): self

// Set image provider override
public function setImageProvider(string $provider): self

// Clear cache for specific request
public function clearCache(ContentRequest $request): bool

// Clear all content generation cache
public function clearAllCache(): bool
```

## Testing

The package includes comprehensive test coverage with unit tests, integration tests, and property-based tests.

### Running Tests

```bash
# Run all tests
vendor/bin/pest

# Run specific test suite
vendor/bin/pest --testsuite=Unit
vendor/bin/pest --testsuite=Feature
vendor/bin/pest --testsuite=Property

# Run with coverage
vendor/bin/pest --coverage
```

### Mock Mode for Testing

Enable mock mode in your test environment:

```php
// In your test
Config::set('ai-content-generator.testing.mock_mode', true);

// Mock providers are automatically used
$response = AIContentGenerator::generate($request);
```

### Test Factories

Use provided factories for testing:

```php
use Tests\Factories\ContentRequestFactory;
use Tests\Factories\AuthorPersonaFactory;
use Tests\Factories\LocaleConfigurationFactory;

// Generate random test data
$request = ContentRequestFactory::random();
$author = AuthorPersonaFactory::random();
$locale = LocaleConfigurationFactory::random();
```

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Ensure all tests pass (`vendor/bin/pest`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding standards
- Write comprehensive PHPDoc comments
- Maintain test coverage above 80%
- Use type hints for all parameters and return types

## Security

If you discover any security-related issues, please email security@w3.net.tr instead of using the issue tracker.

## Credits

- **ErdiKoroglu** - Package Author and Maintainer
- All contributors who have helped improve this package

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes and version history.

## Support

- **Documentation**: [https://github.com/erdikoroglu/AiContentGenerator](https://github.com/erdikoroglu/AiContentGenerator)
- **Issues**: [GitHub Issues](https://github.com/erdikoroglu/AiContentGenerator/issues)
- **Discussions**: [GitHub Discussions](https://github.com/erdikoroglu/AiContentGenerator/discussions)
