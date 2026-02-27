<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when contact link validation fails.
 * 
 * This exception is thrown when content does not contain exactly one contact link,
 * or when the contact link does not have the required attributes (target="_blank", rel="nofollow"),
 * or when social media links are detected.
 */
class ContactLinkException extends ValidationException
{
    /**
     * The number of contact links found.
     *
     * @var int|null
     */
    protected ?int $linkCount = null;

    /**
     * The expected contact URL.
     *
     * @var string|null
     */
    protected ?string $expectedUrl = null;

    /**
     * Create a new contact link exception instance.
     *
     * @param int|null $linkCount The number of contact links found
     * @param string|null $expectedUrl The expected contact URL
     * @param string $message The exception message
     * @param array $errors The validation errors
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?int $linkCount = null,
        ?string $expectedUrl = null,
        string $message = "Contact link validation failed",
        array $errors = [],
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        $this->linkCount = $linkCount;
        $this->expectedUrl = $expectedUrl;
        
        if ($linkCount !== null) {
            $message .= sprintf(
                ". Found %d contact link(s), expected exactly 1",
                $linkCount
            );
        }
        
        parent::__construct($message, $errors, 'ContactLinkValidator', $code, $previous);
    }

    /**
     * Get the number of contact links found.
     *
     * @return int|null
     */
    public function getLinkCount(): ?int
    {
        return $this->linkCount;
    }

    /**
     * Get the expected contact URL.
     *
     * @return string|null
     */
    public function getExpectedUrl(): ?string
    {
        return $this->expectedUrl;
    }
}
