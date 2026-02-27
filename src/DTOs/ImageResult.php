<?php

namespace ErdiKoroglu\AIContentGenerator\DTOs;

/**
 * Image Result DTO
 * 
 * Contains image information returned from image providers.
 */
class ImageResult
{
    /**
     * @param string $url Image URL
     * @param string $altText Alt text suggestion for the image
     * @param string|null $attribution Attribution information (if required by provider)
     * @param float $relevanceScore Relevance score (0.0 to 1.0)
     * @param int $width Image width in pixels
     * @param int $height Image height in pixels
     */
    public function __construct(
        public string $url,
        public string $altText,
        public ?string $attribution,
        public float $relevanceScore,
        public int $width,
        public int $height
    ) {
    }

    /**
     * Create ImageResult from array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'],
            altText: $data['alt_text'] ?? $data['altText'] ?? '',
            attribution: $data['attribution'] ?? null,
            relevanceScore: (float) ($data['relevance_score'] ?? $data['relevanceScore'] ?? 0.0),
            width: (int) ($data['width'] ?? 0),
            height: (int) ($data['height'] ?? 0)
        );
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'alt_text' => $this->altText,
            'attribution' => $this->attribution,
            'relevance_score' => $this->relevanceScore,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
