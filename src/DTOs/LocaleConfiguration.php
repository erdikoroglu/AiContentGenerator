<?php

namespace ErdiKoroglu\AIContentGenerator\DTOs;

/**
 * Locale Configuration DTO
 * 
 * Contains locale, language, country and currency information for content generation.
 */
class LocaleConfiguration
{
    /**
     * @param string $locale Locale code (e.g., "en_US", "tr_TR", "de_DE")
     * @param string $localeName Locale name (e.g., "English", "Türkçe", "Deutsch")
     * @param string $targetCountry Target country code
     * @param string $currency Currency code for price-related content
     */
    public function __construct(
        public string $locale,
        public string $localeName,
        public string $targetCountry,
        public string $currency
    ) {
    }

    /**
     * Create LocaleConfiguration from array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            locale: $data['locale'] ?? 'en_US',
            localeName: $data['locale_name'] ?? 'English',
            targetCountry: $data['target_country'] ?? 'US',
            currency: $data['currency'] ?? 'USD'
        );
    }

    /**
     * Convert to array
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'locale' => $this->locale,
            'locale_name' => $this->localeName,
            'target_country' => $this->targetCountry,
            'currency' => $this->currency,
        ];
    }
}
