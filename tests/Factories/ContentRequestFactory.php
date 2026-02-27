<?php

declare(strict_types=1);

namespace ErdiKoroglu\AIContentGenerator\Tests\Factories;

use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;
use ErdiKoroglu\AIContentGenerator\DTOs\LocaleConfiguration;
use ErdiKoroglu\AIContentGenerator\Models\AuthorPersona;
use Faker\Factory as FakerFactory;
use Faker\Generator;

/**
 * Content Request Factory
 *
 * Factory for generating ContentRequest instances with sensible defaults
 * and realistic data for testing purposes.
 *
 * @package ErdiKoroglu\AIContentGenerator\Tests\Factories
 */
class ContentRequestFactory
{
    private static ?Generator $faker = null;

    /**
     * Get Faker instance
     *
     * @return Generator
     */
    private static function faker(): Generator
    {
        if (self::$faker === null) {
            self::$faker = FakerFactory::create();
        }

        return self::$faker;
    }

    /**
     * Create a ContentRequest with default values
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return ContentRequest
     */
    public static function make(array $overrides = []): ContentRequest
    {
        $faker = self::faker();

        $defaults = [
            'focusKeyword' => $overrides['focusKeyword'] ?? $faker->words(2, true),
            'relatedKeywords' => $overrides['relatedKeywords'] ?? $faker->words(5),
            'searchIntent' => $overrides['searchIntent'] ?? $faker->randomElement([
                'informational',
                'navigational',
                'transactional',
                'commercial'
            ]),
            'contentType' => $overrides['contentType'] ?? $faker->randomElement([
                'how-to',
                'concept',
                'news'
            ]),
            'locale' => $overrides['locale'] ?? LocaleConfigurationFactory::make(),
            'author' => $overrides['author'] ?? AuthorPersonaFactory::make(),
            'wordCountMin' => $overrides['wordCountMin'] ?? $faker->numberBetween(500, 800),
            'wordCountMax' => $overrides['wordCountMax'] ?? $faker->numberBetween(1000, 2000),
            'introWordCount' => $overrides['introWordCount'] ?? $faker->numberBetween(80, 120),
            'conclusionWordCount' => $overrides['conclusionWordCount'] ?? $faker->numberBetween(80, 120),
            'mainContentWordCount' => $overrides['mainContentWordCount'] ?? $faker->numberBetween(400, 800),
            'faqMinCount' => $overrides['faqMinCount'] ?? $faker->numberBetween(3, 7),
            'contactUrl' => $overrides['contactUrl'] ?? $faker->url(),
            'aiProvider' => $overrides['aiProvider'] ?? null,
            'imageProvider' => $overrides['imageProvider'] ?? null,
        ];

        return new ContentRequest(
            focusKeyword: $defaults['focusKeyword'],
            relatedKeywords: $defaults['relatedKeywords'],
            searchIntent: $defaults['searchIntent'],
            contentType: $defaults['contentType'],
            locale: $defaults['locale'],
            author: $defaults['author'],
            wordCountMin: $defaults['wordCountMin'],
            wordCountMax: $defaults['wordCountMax'],
            introWordCount: $defaults['introWordCount'],
            conclusionWordCount: $defaults['conclusionWordCount'],
            mainContentWordCount: $defaults['mainContentWordCount'],
            faqMinCount: $defaults['faqMinCount'],
            contactUrl: $defaults['contactUrl'],
            aiProvider: $defaults['aiProvider'],
            imageProvider: $defaults['imageProvider']
        );
    }

    /**
     * Create a ContentRequest with random values
     *
     * Alias for make() for better readability in tests
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return ContentRequest
     */
    public static function random(array $overrides = []): ContentRequest
    {
        return self::make($overrides);
    }

    /**
     * Create a ContentRequest for "how-to" content type
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return ContentRequest
     */
    public static function howTo(array $overrides = []): ContentRequest
    {
        return self::make(array_merge(['contentType' => 'how-to'], $overrides));
    }

    /**
     * Create a ContentRequest for "concept" content type
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return ContentRequest
     */
    public static function concept(array $overrides = []): ContentRequest
    {
        return self::make(array_merge(['contentType' => 'concept'], $overrides));
    }

    /**
     * Create a ContentRequest for "news" content type
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return ContentRequest
     */
    public static function news(array $overrides = []): ContentRequest
    {
        return self::make(array_merge(['contentType' => 'news'], $overrides));
    }

    /**
     * Create a ContentRequest with informational search intent
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return ContentRequest
     */
    public static function informational(array $overrides = []): ContentRequest
    {
        return self::make(array_merge(['searchIntent' => 'informational'], $overrides));
    }

    /**
     * Create a ContentRequest with transactional search intent
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return ContentRequest
     */
    public static function transactional(array $overrides = []): ContentRequest
    {
        return self::make(array_merge(['searchIntent' => 'transactional'], $overrides));
    }

    /**
     * Create a ContentRequest with specific word count range
     *
     * @param int $min Minimum word count
     * @param int $max Maximum word count
     * @param array<string, mixed> $overrides Additional overrides
     * @return ContentRequest
     */
    public static function withWordCount(int $min, int $max, array $overrides = []): ContentRequest
    {
        return self::make(array_merge([
            'wordCountMin' => $min,
            'wordCountMax' => $max
        ], $overrides));
    }

    /**
     * Create a ContentRequest with specific focus keyword
     *
     * @param string $keyword Focus keyword
     * @param array<string, mixed> $overrides Additional overrides
     * @return ContentRequest
     */
    public static function withKeyword(string $keyword, array $overrides = []): ContentRequest
    {
        return self::make(array_merge(['focusKeyword' => $keyword], $overrides));
    }

    /**
     * Create a ContentRequest with specific locale
     *
     * @param LocaleConfiguration $locale Locale configuration
     * @param array<string, mixed> $overrides Additional overrides
     * @return ContentRequest
     */
    public static function withLocale(LocaleConfiguration $locale, array $overrides = []): ContentRequest
    {
        return self::make(array_merge(['locale' => $locale], $overrides));
    }

    /**
     * Create a ContentRequest with specific author
     *
     * @param AuthorPersona $author Author persona
     * @param array<string, mixed> $overrides Additional overrides
     * @return ContentRequest
     */
    public static function withAuthor(AuthorPersona $author, array $overrides = []): ContentRequest
    {
        return self::make(array_merge(['author' => $author], $overrides));
    }
}
