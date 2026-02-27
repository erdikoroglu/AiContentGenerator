<?php

namespace ErdiKoroglu\AIContentGenerator\Validators;

use DOMDocument;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;

/**
 * Word Count Validator
 * 
 * Validates word counts:
 * - Total word count range
 * - Introduction word count (±10% tolerance)
 * - Conclusion word count (±10% tolerance)
 * 
 * Requirements: 9.6, 9.7, 9.8
 */
class WordCountValidator implements ValidatorInterface
{
    private array $errors = [];

    /**
     * Validate word counts.
     *
     * @param string $content The HTML content to validate
     * @param ContentRequest $request The original content generation request
     * @return bool True if validation passes, false otherwise
     */
    public function validate(string $content, ContentRequest $request): bool
    {
        $this->errors = [];

        // Parse HTML to identify sections
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Get total word count
        $plainText = strip_tags($content);
        $totalWords = $this->countWords($plainText);

        // Validate total word count range
        if ($totalWords < $request->wordCountMin) {
            $this->errors[] = sprintf(
                'Total word count too low: %d words (minimum: %d)',
                $totalWords,
                $request->wordCountMin
            );
            return false;
        }

        if ($totalWords > $request->wordCountMax) {
            $this->errors[] = sprintf(
                'Total word count too high: %d words (maximum: %d)',
                $totalWords,
                $request->wordCountMax
            );
            return false;
        }

        // Try to identify introduction and conclusion sections
        $sections = $this->identifySections($dom);

        // Validate introduction word count (±10% tolerance)
        if (isset($sections['introduction'])) {
            $introWords = $this->countWords($sections['introduction']);
            $introTarget = $request->introWordCount;
            $introMin = $introTarget * 0.9;
            $introMax = $introTarget * 1.1;

            if ($introWords < $introMin || $introWords > $introMax) {
                $this->errors[] = sprintf(
                    'Introduction word count out of range: %d words (target: %d ±10%%, range: %.0f-%.0f)',
                    $introWords,
                    $introTarget,
                    $introMin,
                    $introMax
                );
                return false;
            }
        }

        // Validate conclusion word count (±10% tolerance)
        if (isset($sections['conclusion'])) {
            $conclusionWords = $this->countWords($sections['conclusion']);
            $conclusionTarget = $request->conclusionWordCount;
            $conclusionMin = $conclusionTarget * 0.9;
            $conclusionMax = $conclusionTarget * 1.1;

            if ($conclusionWords < $conclusionMin || $conclusionWords > $conclusionMax) {
                $this->errors[] = sprintf(
                    'Conclusion word count out of range: %d words (target: %d ±10%%, range: %.0f-%.0f)',
                    $conclusionWords,
                    $conclusionTarget,
                    $conclusionMin,
                    $conclusionMax
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Count words in text.
     *
     * @param string $text The text to count words in
     * @return int Word count
     */
    private function countWords(string $text): int
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    /**
     * Identify introduction and conclusion sections from HTML.
     *
     * @param DOMDocument $dom The DOM document
     * @return array Array with 'introduction' and 'conclusion' keys
     */
    private function identifySections(DOMDocument $dom): array
    {
        $sections = [];
        $xpath = new \DOMXPath($dom);

        // Look for introduction section (first content before first H2, or section with "introduction" in heading)
        $h2Tags = $dom->getElementsByTagName('h2');
        
        if ($h2Tags->length > 0) {
            $firstH2 = $h2Tags->item(0);
            
            // Check if first H2 is introduction
            $h2Text = strtolower($firstH2->textContent);
            if (str_contains($h2Text, 'introduction') || str_contains($h2Text, 'giriş')) {
                // Get content between this H2 and next H2
                $sections['introduction'] = $this->getContentBetweenHeadings($dom, $firstH2, $h2Tags->item(1));
            } else {
                // Get content before first H2 as introduction
                $sections['introduction'] = $this->getContentBeforeElement($dom, $firstH2);
            }

            // Look for conclusion section (last H2 with "conclusion" or "sonuç" in text)
            $lastH2 = null;
            for ($i = $h2Tags->length - 1; $i >= 0; $i--) {
                $h2 = $h2Tags->item($i);
                $h2Text = strtolower($h2->textContent);
                if (str_contains($h2Text, 'conclusion') || str_contains($h2Text, 'sonuç') || 
                    str_contains($h2Text, 'summary') || str_contains($h2Text, 'özet')) {
                    $lastH2 = $h2;
                    break;
                }
            }

            if ($lastH2) {
                $sections['conclusion'] = $this->getContentAfterElement($dom, $lastH2);
            }
        }

        return $sections;
    }

    /**
     * Get text content before a specific element.
     *
     * @param DOMDocument $dom The DOM document
     * @param \DOMElement $element The element
     * @return string Text content
     */
    private function getContentBeforeElement(DOMDocument $dom, \DOMElement $element): string
    {
        $content = '';
        $node = $dom->documentElement->firstChild;
        
        while ($node && $node !== $element) {
            if ($node->nodeType === XML_ELEMENT_NODE || $node->nodeType === XML_TEXT_NODE) {
                $content .= $node->textContent . ' ';
            }
            $node = $node->nextSibling;
        }

        return $content;
    }

    /**
     * Get text content after a specific element.
     *
     * @param DOMDocument $dom The DOM document
     * @param \DOMElement $element The element
     * @return string Text content
     */
    private function getContentAfterElement(DOMDocument $dom, \DOMElement $element): string
    {
        $content = '';
        $node = $element->nextSibling;
        
        while ($node) {
            if ($node->nodeType === XML_ELEMENT_NODE || $node->nodeType === XML_TEXT_NODE) {
                $content .= $node->textContent . ' ';
            }
            $node = $node->nextSibling;
        }

        return $content;
    }

    /**
     * Get text content between two headings.
     *
     * @param DOMDocument $dom The DOM document
     * @param \DOMElement $startElement The start element
     * @param \DOMElement|null $endElement The end element (null for end of document)
     * @return string Text content
     */
    private function getContentBetweenHeadings(DOMDocument $dom, \DOMElement $startElement, ?\DOMElement $endElement): string
    {
        $content = '';
        $node = $startElement->nextSibling;
        
        while ($node && $node !== $endElement) {
            if ($node->nodeType === XML_ELEMENT_NODE || $node->nodeType === XML_TEXT_NODE) {
                $content .= $node->textContent . ' ';
            }
            $node = $node->nextSibling;
        }

        return $content;
    }

    /**
     * Get validation errors from the last validation attempt.
     *
     * @return array Array of error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the name of this validator.
     *
     * @return string The validator name
     */
    public function getName(): string
    {
        return 'WordCountValidator';
    }
}
