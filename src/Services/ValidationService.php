<?php

namespace ErdiKoroglu\AIContentGenerator\Services;

use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentResponse;
use ErdiKoroglu\AIContentGenerator\Validators\ValidatorInterface;

/**
 * ValidationService
 * 
 * Orchestrates all validators using the Chain of Responsibility pattern.
 * Manages a collection of validators and executes them in sequence.
 * 
 * Requirements: 16.6, 18.5
 */
class ValidationService
{
    /**
     * @var array<ValidatorInterface> Registered validators
     */
    private array $validators = [];

    /**
     * Add a validator to the chain.
     *
     * @param ValidatorInterface $validator The validator to add
     * @return self For method chaining
     */
    public function addValidator(ValidatorInterface $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    /**
     * Get all registered validators.
     *
     * @return array<ValidatorInterface> Array of validators
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * Run all validators and return array of errors.
     * 
     * Executes all validators in sequence and collects errors from each.
     * Returns an empty array if all validations pass.
     *
     * @param ContentResponse $content The generated content to validate
     * @param ContentRequest $request The original content generation request
     * @return array<string, array<string>> Array of errors keyed by validator name, empty if valid
     */
    public function validate(ContentResponse $content, ContentRequest $request): array
    {
        $allErrors = [];

        foreach ($this->validators as $validator) {
            $isValid = $validator->validate($content->content, $request);
            
            if (!$isValid) {
                $errors = $validator->getErrors();
                if (!empty($errors)) {
                    $allErrors[$validator->getName()] = $errors;
                }
            }
        }

        return $allErrors;
    }
}
