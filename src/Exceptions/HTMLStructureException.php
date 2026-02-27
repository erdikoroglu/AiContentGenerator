<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when HTML structure validation fails.
 * 
 * This exception is thrown when content has invalid HTML, improper heading hierarchy,
 * missing paragraph tags, Markdown syntax, or inline styles/scripts.
 */
class HTMLStructureException extends ValidationException
{
    /**
     * The specific HTML structure issue detected.
     *
     * @var string|null
     */
    protected ?string $structureIssue = null;

    /**
     * Create a new HTML structure exception instance.
     *
     * @param string|null $structureIssue The specific HTML structure issue
     * @param string $message The exception message
     * @param array $errors The validation errors
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $structureIssue = null,
        string $message = "HTML structure validation failed",
        array $errors = [],
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        $this->structureIssue = $structureIssue;
        
        if ($structureIssue) {
            $message .= ". Issue: {$structureIssue}";
        }
        
        parent::__construct($message, $errors, 'HTMLStructureValidator', $code, $previous);
    }

    /**
     * Get the specific HTML structure issue detected.
     *
     * @return string|null
     */
    public function getStructureIssue(): ?string
    {
        return $this->structureIssue;
    }
}
