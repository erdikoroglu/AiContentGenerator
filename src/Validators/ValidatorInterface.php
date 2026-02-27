<?php

namespace ErdiKoroglu\AIContentGenerator\Validators;

use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;

/**
 * Interface for content validators in the Chain of Responsibility pattern.
 * 
 * Each validator is responsible for checking a specific aspect of the generated content
 * (e.g., keyword density, HTML structure, AdSense compliance, word count, contact links).
 */
interface ValidatorInterface
{
    /**
     * Validate the content against specific rules.
     *
     * @param string $content The HTML content to validate
     * @param ContentRequest $request The original content generation request
     * @return bool True if validation passes, false otherwise
     */
    public function validate(string $content, ContentRequest $request): bool;

    /**
     * Get validation errors from the last validation attempt.
     *
     * @return array Array of error messages
     */
    public function getErrors(): array;

    /**
     * Get the name of this validator.
     *
     * @return string The validator name (e.g., "KeywordDensityValidator")
     */
    public function getName(): string;
}
