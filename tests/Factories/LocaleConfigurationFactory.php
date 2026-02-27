<?php

declare(strict_types=1);

namespace ErdiKoroglu\AIContentGenerator\Tests\Factories;

use ErdiKoroglu\AIContentGenerator\DTOs\LocaleConfiguration;
use Faker\Factory as FakerFactory;
use Faker\Generator;

/**
 * Locale Configuration Factory
 *
 * Factory for generating LocaleConfiguration instances with sensible defaults
 * and realistic data for testing purposes.
 *
 * @package ErdiKoroglu\AIContentGenerator\Tests\Factories
 */
class LocaleConfigurationFactory
{
    private static ?Generator $faker = null;

    /**
     * Predefined locale configurations for common locales
     *
     * @var array<string, array<string, string>>
     */
    private static array $locales = [
        'en_US' => [
            'locale' => 'en_US',
            'localeName' => 'English',
            'targetCountry' => 'US',
            'currency' => 'USD',
        ],
        'en_GB' => [
            'locale' => 'en_GB',
            'localeName' => 'English (UK)',
            'targetCountry' => 'GB',
            'currency' => 'GBP',
        ],
        'tr_TR' => [
            'locale' => 'tr_TR',
            'localeName' => 'Türkçe',
            'targetCountry' => 'TR',
            'currency' => 'TRY',
        ],
        'de_DE' => [
            'locale' => 'de_DE',
            'localeName' => 'Deutsch',
            'targetCountry' => 'DE',
            'currency' => 'EUR',
        ],
        'fr_FR' => [
            'locale' => 'fr_FR',
            'localeName' => 'Français',
            'targetCountry' => 'FR',
            'currency' => 'EUR',
        ],
        'es_ES' => [
            'locale' => 'es_ES',
            'localeName' => 'Español',
            'targetCountry' => 'ES',
            'currency' => 'EUR',
        ],
        'it_IT' => [
            'locale' => 'it_IT',
            'localeName' => 'Italiano',
            'targetCountry' => 'IT',
            'currency' => 'EUR',
        ],
        'pt_BR' => [
            'locale' => 'pt_BR',
            'localeName' => 'Português (Brasil)',
            'targetCountry' => 'BR',
            'currency' => 'BRL',
        ],
        'ja_JP' => [
            'locale' => 'ja_JP',
            'localeName' => '日本語',
            'targetCountry' => 'JP',
            'currency' => 'JPY',
        ],
        'zh_CN' => [
            'locale' => 'zh_CN',
            'localeName' => '中文 (简体)',
            'targetCountry' => 'CN',
            'currency' => 'CNY',
        ],
    ];

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
     * Create a LocaleConfiguration with default values (en_US)
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return LocaleConfiguration
     */
    public static function make(array $overrides = []): LocaleConfiguration
    {
        $defaults = self::$locales['en_US'];

        return new LocaleConfiguration(
            locale: $overrides['locale'] ?? $defaults['locale'],
            localeName: $overrides['localeName'] ?? $defaults['localeName'],
            targetCountry: $overrides['targetCountry'] ?? $defaults['targetCountry'],
            currency: $overrides['currency'] ?? $defaults['currency']
        );
    }

    /**
     * Create a LocaleConfiguration with random locale
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function random(array $overrides = []): LocaleConfiguration
    {
        $faker = self::faker();
        $localeKey = $faker->randomElement(array_keys(self::$locales));
        $localeData = self::$locales[$localeKey];

        return new LocaleConfiguration(
            locale: $overrides['locale'] ?? $localeData['locale'],
            localeName: $overrides['localeName'] ?? $localeData['localeName'],
            targetCountry: $overrides['targetCountry'] ?? $localeData['targetCountry'],
            currency: $overrides['currency'] ?? $localeData['currency']
        );
    }

    /**
     * Create a LocaleConfiguration for US English
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function enUS(array $overrides = []): LocaleConfiguration
    {
        return self::fromLocaleKey('en_US', $overrides);
    }

    /**
     * Create a LocaleConfiguration for UK English
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function enGB(array $overrides = []): LocaleConfiguration
    {
        return self::fromLocaleKey('en_GB', $overrides);
    }

    /**
     * Create a LocaleConfiguration for Turkish
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function trTR(array $overrides = []): LocaleConfiguration
    {
        return self::fromLocaleKey('tr_TR', $overrides);
    }

    /**
     * Create a LocaleConfiguration for German
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function deDE(array $overrides = []): LocaleConfiguration
    {
        return self::fromLocaleKey('de_DE', $overrides);
    }

    /**
     * Create a LocaleConfiguration for French
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function frFR(array $overrides = []): LocaleConfiguration
    {
        return self::fromLocaleKey('fr_FR', $overrides);
    }

    /**
     * Create a LocaleConfiguration for Spanish
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function esES(array $overrides = []): LocaleConfiguration
    {
        return self::fromLocaleKey('es_ES', $overrides);
    }

    /**
     * Create a LocaleConfiguration for Italian
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function itIT(array $overrides = []): LocaleConfiguration
    {
        return self::fromLocaleKey('it_IT', $overrides);
    }

    /**
     * Create a LocaleConfiguration for Brazilian Portuguese
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function ptBR(array $overrides = []): LocaleConfiguration
    {
        return self::fromLocaleKey('pt_BR', $overrides);
    }

    /**
     * Create a LocaleConfiguration for Japanese
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function jaJP(array $overrides = []): LocaleConfiguration
    {
        return self::fromLocaleKey('ja_JP', $overrides);
    }

    /**
     * Create a LocaleConfiguration for Chinese (Simplified)
     *
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    public static function zhCN(array $overrides = []): LocaleConfiguration
    {
        return self::fromLocaleKey('zh_CN', $overrides);
    }

    /**
     * Create a LocaleConfiguration from a locale key
     *
     * @param string $localeKey Locale key (e.g., 'en_US', 'tr_TR')
     * @param array<string, mixed> $overrides Values to override
     * @return LocaleConfiguration
     */
    private static function fromLocaleKey(string $localeKey, array $overrides = []): LocaleConfiguration
    {
        if (!isset(self::$locales[$localeKey])) {
            throw new \InvalidArgumentException("Unknown locale key: {$localeKey}");
        }

        $localeData = self::$locales[$localeKey];

        return new LocaleConfiguration(
            locale: $overrides['locale'] ?? $localeData['locale'],
            localeName: $overrides['localeName'] ?? $localeData['localeName'],
            targetCountry: $overrides['targetCountry'] ?? $localeData['targetCountry'],
            currency: $overrides['currency'] ?? $localeData['currency']
        );
    }

    /**
     * Get all available locale keys
     *
     * @return array<int, string>
     */
    public static function availableLocales(): array
    {
        return array_keys(self::$locales);
    }
}
