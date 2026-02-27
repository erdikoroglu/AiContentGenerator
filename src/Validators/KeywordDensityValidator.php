<?php

namespace ErdiKoroglu\AIContentGenerator\Validators;

use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;

/**
 * Keyword Density Validator
 * 
 * Validates that keyword density is within acceptable range (0.5%-2.5%)
 * and detects keyword stuffing.
 * 
 * Requirements: 6.4, 6.5, 6.6, 16.1
 */
class KeywordDensityValidator implements ValidatorInterface
{
    private array $errors = [];

    /**
     * Validate keyword density.
     *
     * @param string $content The HTML content to validate
     * @param ContentRequest $request The original content generation request
     * @return bool True if validation passes, false otherwise
     */
    public function validate(string $content, ContentRequest $request): bool
    {
        $this->errors = [];

        // Strip HTML tags to get plain text
        $plainText = strip_tags($content);
        
        // Calculate total word count
        $words = preg_split('/\s+/', trim($plainText), -1, PREG_SPLIT_NO_EMPTY);
        $totalWords = count($words);

        if ($totalWords === 0) {
            $this->errors[] = 'Content has no words';
            return false;
        }

        // Count keyword occurrences (case-insensitive)
        $keyword = $request->focusKeyword;
        $keywordPattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
        preg_match_all($keywordPattern, $plainText, $matches);
        $keywordCount = count($matches[0]);

        // Calculate density as percentage
        $density = ($keywordCount / $totalWords) * 100;

        // Check if density is within acceptable range (0.5% - 2.5%)
        if ($density < 0.5) {
            $this->errors[] = sprintf(
                'Keyword density too low: %.2f%% (minimum: 0.5%%)',
                $density
            );
            return false;
        }

        if ($density > 2.5) {
            $this->errors[] = sprintf(
                'Keyword stuffing detected: %.2f%% density exceeds maximum of 2.5%%',
                $density
            );
            return false;
        }

        return true;
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
        return 'KeywordDensityValidator';
    }
}
