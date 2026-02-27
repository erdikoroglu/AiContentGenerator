<?php

namespace ErdiKoroglu\AIContentGenerator\Exceptions;

/**
 * Exception thrown when keyword density validation fails.
 * 
 * This exception is thrown when the focus keyword density is outside
 * the acceptable range (0.5% - 2.5%) or when keyword stuffing is detected.
 */
class KeywordDensityException extends ValidationException
{
    /**
     * The calculated keyword density.
     *
     * @var float|null
     */
    protected ?float $density = null;

    /**
     * The focus keyword.
     *
     * @var string|null
     */
    protected ?string $keyword = null;

    /**
     * Create a new keyword density exception instance.
     *
     * @param float|null $density The calculated keyword density
     * @param string|null $keyword The focus keyword
     * @param string $message The exception message
     * @param array $errors The validation errors
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(
        ?float $density = null,
        ?string $keyword = null,
        string $message = "Keyword density validation failed",
        array $errors = [],
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        $this->density = $density;
        $this->keyword = $keyword;
        
        if ($density !== null && $keyword !== null) {
            $message .= sprintf(
                ". Keyword '%s' has density of %.2f%% (acceptable range: 0.5%% - 2.5%%)",
                $keyword,
                $density
            );
        }
        
        parent::__construct($message, $errors, 'KeywordDensityValidator', $code, $previous);
    }

    /**
     * Get the calculated keyword density.
     *
     * @return float|null
     */
    public function getDensity(): ?float
    {
        return $this->density;
    }

    /**
     * Get the focus keyword.
     *
     * @return string|null
     */
    public function getKeyword(): ?string
    {
        return $this->keyword;
    }
}
