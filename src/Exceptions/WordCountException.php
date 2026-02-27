<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when word count validation fails.
 * 
 * This exception is thrown when content does not meet the specified word count
 * requirements for total content, introduction, or conclusion sections.
 */
class WordCountException extends ValidationException
{
    /**
     * The actual word count.
     *
     * @var int|null
     */
    protected ?int $actualCount = null;

    /**
     * The expected minimum word count.
     *
     * @var int|null
     */
    protected ?int $expectedMin = null;

    /**
     * The expected maximum word count.
     *
     * @var int|null
     */
    protected ?int $expectedMax = null;

    /**
     * The section that failed validation (e.g., 'total', 'introduction', 'conclusion').
     *
     * @var string|null
     */
    protected ?string $section = null;

    /**
     * Create a new word count exception instance.
     *
     * @param int|null $actualCount The actual word count
     * @param int|null $expectedMin The expected minimum word count
     * @param int|null $expectedMax The expected maximum word count
     * @param string|null $section The section that failed validation
     * @param string $message The exception message
     * @param array $errors The validation errors
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?int $actualCount = null,
        ?int $expectedMin = null,
        ?int $expectedMax = null,
        ?string $section = null,
        string $message = "Word count validation failed",
        array $errors = [],
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        $this->actualCount = $actualCount;
        $this->expectedMin = $expectedMin;
        $this->expectedMax = $expectedMax;
        $this->section = $section;
        
        if ($actualCount !== null && $expectedMin !== null && $expectedMax !== null) {
            $sectionText = $section ? " for {$section}" : "";
            $message .= sprintf(
                "%s. Actual: %d words, Expected: %d-%d words",
                $sectionText,
                $actualCount,
                $expectedMin,
                $expectedMax
            );
        }
        
        parent::__construct($message, $errors, 'WordCountValidator', $code, $previous);
    }

    /**
     * Get the actual word count.
     *
     * @return int|null
     */
    public function getActualCount(): ?int
    {
        return $this->actualCount;
    }

    /**
     * Get the expected minimum word count.
     *
     * @return int|null
     */
    public function getExpectedMin(): ?int
    {
        return $this->expectedMin;
    }

    /**
     * Get the expected maximum word count.
     *
     * @return int|null
     */
    public function getExpectedMax(): ?int
    {
        return $this->expectedMax;
    }

    /**
     * Get the section that failed validation.
     *
     * @return string|null
     */
    public function getSection(): ?string
    {
        return $this->section;
    }
}
