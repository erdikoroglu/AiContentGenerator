<?php

namespace ErdiKoroglu\AIContentGenerator\DTOs;

use ErdiKoroglu\AIContentGenerator\Models\AuthorPersona;

/**
 * Content Request DTO
 * 
 * Contains all input parameters for content generation.
 */
class ContentRequest
{
    /**
     * @param string $focusKeyword Main focus keyword for the content
     * @param array<string> $relatedKeywords Related keywords to incorporate
     * @param string $searchIntent Search intent (informational, navigational, transactional, commercial)
     * @param string $contentType Content type (how-to, concept, news)
     * @param LocaleConfiguration $locale Locale configuration
     * @param AuthorPersona $author Author persona
     * @param int $wordCountMin Minimum word count
     * @param int $wordCountMax Maximum word count
     * @param int $introWordCount Introduction word count target
     * @param int $conclusionWordCount Conclusion word count target
     * @param int $mainContentWordCount Main content word count target
     * @param int $faqMinCount Minimum FAQ count
     * @param string $contactUrl Contact URL to include in content
     * @param string|null $aiProvider AI provider to use (optional, uses default if null)
     * @param string|null $imageProvider Image provider to use (optional, uses default if null)
     */
    public function __construct(
        public string $focusKeyword,
        public array $relatedKeywords,
        public string $searchIntent,
        public string $contentType,
        public LocaleConfiguration $locale,
        public AuthorPersona $author,
        public int $wordCountMin,
        public int $wordCountMax,
        public int $introWordCount,
        public int $conclusionWordCount,
        public int $mainContentWordCount,
        public int $faqMinCount,
        public string $contactUrl,
        public ?string $aiProvider = null,
        public ?string $imageProvider = null
    ) {
    }

    /**
     * Create ContentRequest from array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            focusKeyword: $data['focus_keyword'] ?? $data['focusKeyword'],
            relatedKeywords: $data['related_keywords'] ?? $data['relatedKeywords'] ?? [],
            searchIntent: $data['search_intent'] ?? $data['searchIntent'] ?? 'informational',
            contentType: $data['content_type'] ?? $data['contentType'] ?? 'concept',
            locale: $data['locale'] instanceof LocaleConfiguration 
                ? $data['locale'] 
                : LocaleConfiguration::fromArray($data['locale'] ?? []),
            author: $data['author'],
            wordCountMin: (int) ($data['word_count_min'] ?? $data['wordCountMin'] ?? 800),
            wordCountMax: (int) ($data['word_count_max'] ?? $data['wordCountMax'] ?? 1500),
            introWordCount: (int) ($data['intro_word_count'] ?? $data['introWordCount'] ?? 100),
            conclusionWordCount: (int) ($data['conclusion_word_count'] ?? $data['conclusionWordCount'] ?? 100),
            mainContentWordCount: (int) ($data['main_content_word_count'] ?? $data['mainContentWordCount'] ?? 600),
            faqMinCount: (int) ($data['faq_min_count'] ?? $data['faqMinCount'] ?? 3),
            contactUrl: $data['contact_url'] ?? $data['contactUrl'],
            aiProvider: $data['ai_provider'] ?? $data['aiProvider'] ?? null,
            imageProvider: $data['image_provider'] ?? $data['imageProvider'] ?? null
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
            'focus_keyword' => $this->focusKeyword,
            'related_keywords' => $this->relatedKeywords,
            'search_intent' => $this->searchIntent,
            'content_type' => $this->contentType,
            'locale' => $this->locale->toArray(),
            'author' => $this->author->toArray(),
            'word_count_min' => $this->wordCountMin,
            'word_count_max' => $this->wordCountMax,
            'intro_word_count' => $this->introWordCount,
            'conclusion_word_count' => $this->conclusionWordCount,
            'main_content_word_count' => $this->mainContentWordCount,
            'faq_min_count' => $this->faqMinCount,
            'contact_url' => $this->contactUrl,
            'ai_provider' => $this->aiProvider,
            'image_provider' => $this->imageProvider,
        ];
    }
}
