<?php

namespace ErdiKoroglu\AIContentGenerator\DTOs;

use Carbon\Carbon;

/**
 * Content Response DTO
 * 
 * Contains generated content and metadata.
 */
class ContentResponse
{
    /**
     * @param string $title Generated title
     * @param string $metaDescription SEO meta description
     * @param string $excerpt Content excerpt
     * @param string $focusKeyword Focus keyword used
     * @param string $content Generated HTML content
     * @param array<array{question: string, answer: string}> $faqs FAQ items
     * @param array<ImageResult> $images Image results
     * @param int $wordCount Actual word count
     * @param Carbon $generatedAt Generation timestamp
     */
    public function __construct(
        public string $title,
        public string $metaDescription,
        public string $excerpt,
        public string $focusKeyword,
        public string $content,
        public array $faqs,
        public array $images,
        public int $wordCount,
        public Carbon $generatedAt
    ) {
    }

    /**
     * Convert to JSON string
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'meta_description' => $this->metaDescription,
            'excerpt' => $this->excerpt,
            'focus_keyword' => $this->focusKeyword,
            'content' => $this->content,
            'faqs' => $this->faqs,
            'images' => array_map(fn(ImageResult $image) => $image->toArray(), $this->images),
            'word_count' => $this->wordCount,
            'generated_at' => $this->generatedAt->toIso8601String(),
        ];
    }

    /**
     * Create ContentResponse from array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $images = array_map(
            fn($imageData) => $imageData instanceof ImageResult 
                ? $imageData 
                : ImageResult::fromArray($imageData),
            $data['images'] ?? []
        );

        return new self(
            title: $data['title'],
            metaDescription: $data['meta_description'] ?? $data['metaDescription'],
            excerpt: $data['excerpt'],
            focusKeyword: $data['focus_keyword'] ?? $data['focusKeyword'],
            content: $data['content'],
            faqs: $data['faqs'] ?? [],
            images: $images,
            wordCount: (int) ($data['word_count'] ?? $data['wordCount']),
            generatedAt: $data['generated_at'] instanceof Carbon 
                ? $data['generated_at'] 
                : Carbon::parse($data['generated_at'] ?? $data['generatedAt'] ?? now())
        );
    }
}
