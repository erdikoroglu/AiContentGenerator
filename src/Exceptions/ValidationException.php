<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when content validation fails.
 * 
 * This is the base exception for all validation-related errors,
 * including keyword density, AdSense compliance, HTML structure,
 * word count, and contact link validation failures.
 */
class ValidationException extends AIContentGeneratorException
{
    /**
     * The name of the validator that failed.
     *
     * @var string|null
     */
    protected ?string $validatorName = null;

    /**
     * The validation errors.
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Create a new validation exception instance.
     *
     * @param string $message The exception message
     * @param array $errors The validation errors
     * @param string|null $validatorName The name of the validator that failed
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        string $message = "Content validation failed",
        array $errors = [],
        ?string $validatorName = null,
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        $this->errors = $errors;
        $this->validatorName = $validatorName;
        
        if ($validatorName) {
            $message = "[{$validatorName}] {$message}";
        }
        
        if (!empty($errors)) {
            $message .= ": " . implode(', ', $errors);
        }
        
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the validator name that failed.
     *
     * @return string|null
     */
    public function getValidatorName(): ?string
    {
        return $this->validatorName;
    }
}
