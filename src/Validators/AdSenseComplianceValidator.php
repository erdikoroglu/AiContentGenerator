<?php

namespace ErdiKoroglu\AIContentGenerator\Validators;

use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;

/**
 * AdSense Compliance Validator
 * 
 * Validates content against Google AdSense policies:
 * - Adult content
 * - Violence and hate speech
 * - Illegal activities
 * - Profanity
 * - Dangerous products
 * 
 * Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.8
 */
class AdSenseComplianceValidator implements ValidatorInterface
{
    private array $errors = [];

    /**
     * Blocklists for different policy categories
     */
    private const ADULT_CONTENT_KEYWORDS = [
        'porn', 'xxx', 'sex', 'nude', 'naked', 'adult content', 'explicit',
        'erotic', 'sexual', 'nsfw'
    ];

    private const VIOLENCE_HATE_KEYWORDS = [
        'kill', 'murder', 'violence', 'hate speech', 'terrorist', 'terrorism',
        'genocide', 'torture', 'abuse', 'assault', 'racist', 'racism'
    ];

    private const ILLEGAL_ACTIVITIES_KEYWORDS = [
        'illegal', 'drug trafficking', 'counterfeit', 'piracy', 'hacking',
        'fraud', 'scam', 'money laundering', 'stolen', 'contraband'
    ];

    private const PROFANITY_KEYWORDS = [
        'fuck', 'shit', 'damn', 'bitch', 'bastard', 'ass', 'crap',
        'hell', 'piss', 'cock', 'dick'
    ];

    private const DANGEROUS_PRODUCTS_KEYWORDS = [
        'explosives', 'weapons', 'guns', 'ammunition', 'bomb', 'grenade',
        'dangerous chemicals', 'poison', 'toxic', 'hazardous materials'
    ];

    private const PROFANITY_THRESHOLD = 3;

    /**
     * Validate AdSense compliance.
     *
     * @param string $content The HTML content to validate
     * @param ContentRequest $request The original content generation request
     * @return bool True if validation passes, false otherwise
     */
    public function validate(string $content, ContentRequest $request): bool
    {
        $this->errors = [];

        // Strip HTML tags to get plain text
        $plainText = strtolower(strip_tags($content));

        // Check for adult content
        if ($this->containsBlockedKeywords($plainText, self::ADULT_CONTENT_KEYWORDS)) {
            $this->errors[] = 'AdSense policy violation: adult content detected';
            return false;
        }

        // Check for violence and hate speech
        if ($this->containsBlockedKeywords($plainText, self::VIOLENCE_HATE_KEYWORDS)) {
            $this->errors[] = 'AdSense policy violation: violence or hate speech detected';
            return false;
        }

        // Check for illegal activities
        if ($this->containsBlockedKeywords($plainText, self::ILLEGAL_ACTIVITIES_KEYWORDS)) {
            $this->errors[] = 'AdSense policy violation: illegal activities content detected';
            return false;
        }

        // Check for dangerous products
        if ($this->containsBlockedKeywords($plainText, self::DANGEROUS_PRODUCTS_KEYWORDS)) {
            $this->errors[] = 'AdSense policy violation: dangerous products or activities detected';
            return false;
        }

        // Check for excessive profanity
        $profanityCount = $this->countBlockedKeywords($plainText, self::PROFANITY_KEYWORDS);
        if ($profanityCount > self::PROFANITY_THRESHOLD) {
            $this->errors[] = sprintf(
                'AdSense policy violation: excessive profanity detected (%d occurrences, threshold: %d)',
                $profanityCount,
                self::PROFANITY_THRESHOLD
            );
            return false;
        }

        return true;
    }

    /**
     * Check if content contains any blocked keywords.
     *
     * @param string $content Lowercase plain text content
     * @param array $keywords Array of blocked keywords
     * @return bool True if any blocked keyword is found
     */
    private function containsBlockedKeywords(string $content, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($content, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Count occurrences of blocked keywords.
     *
     * @param string $content Lowercase plain text content
     * @param array $keywords Array of blocked keywords
     * @return int Total count of blocked keyword occurrences
     */
    private function countBlockedKeywords(string $content, array $keywords): int
    {
        $count = 0;
        foreach ($keywords as $keyword) {
            $count += substr_count($content, strtolower($keyword));
        }
        return $count;
    }

    /**
     * Get validation errors from the last validation attempt.
     *
     * @return array Array of error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the name of this validator.
     *
     * @return string The validator name
     */
    public function getName(): string
    {
        return 'AdSenseComplianceValidator';
    }
}
