<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when content violates Google AdSense policies.
 * 
 * This exception is thrown when content contains prohibited material such as
 * adult content, violence, hate speech, illegal activities, excessive profanity,
 * or dangerous products/activities.
 */
class AdSenseComplianceException extends ValidationException
{
    /**
     * The specific policy category that was violated.
     *
     * @var string|null
     */
    protected ?string $policyCategory = null;

    /**
     * Create a new AdSense compliance exception instance.
     *
     * @param string|null $policyCategory The specific policy category violated
     * @param string $message The exception message
     * @param array $errors The validation errors
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?string $policyCategory = null,
        string $message = "Content violates Google AdSense policies",
        array $errors = [],
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        $this->policyCategory = $policyCategory;
        
        if ($policyCategory) {
            $message .= ". Policy violated: {$policyCategory}";
        }
        
        parent::__construct($message, $errors, 'AdSenseComplianceValidator', $code, $previous);
    }

    /**
     * Get the specific policy category that was violated.
     *
     * @return string|null
     */
    public function getPolicyCategory(): ?string
    {
        return $this->policyCategory;
    }
}
