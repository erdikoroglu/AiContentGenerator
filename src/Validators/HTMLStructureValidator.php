<?php

namespace ErdiKoroglu\AIContentGenerator\Validators;

use DOMDocument;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;

/**
 * HTML Structure Validator
 * 
 * Validates HTML structure:
 * - Valid HTML parsing
 * - H2/H3 hierarchy
 * - Paragraph tag wrapping
 * - No markdown syntax
 * - No inline styles/scripts
 * 
 * Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.8, 12.9
 */
class HTMLStructureValidator implements ValidatorInterface
{
    private array $errors = [];

    /**
     * Validate HTML structure.
     *
     * @param string $content The HTML content to validate
     * @param ContentRequest $request The original content generation request
     * @return bool True if validation passes, false otherwise
     */
    public function validate(string $content, ContentRequest $request): bool
    {
        $this->errors = [];

        // Check for markdown syntax
        if ($this->containsMarkdownSyntax($content)) {
            $this->errors[] = 'Content contains Markdown formatting instead of HTML';
            return false;
        }

        // Parse HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $parseErrors = libxml_get_errors();
        libxml_clear_errors();

        if (!$loaded || !empty($parseErrors)) {
            $this->errors[] = 'Invalid HTML: content cannot be parsed';
            return false;
        }

        // Check for inline styles
        if ($this->hasInlineStyles($dom)) {
            $this->errors[] = 'Content contains inline styles';
            return false;
        }

        // Check for inline scripts
        if ($this->hasScripts($dom)) {
            $this->errors[] = 'Content contains script tags';
            return false;
        }

        // Check for H2 tags (at least 2 required for main sections)
        $h2Tags = $dom->getElementsByTagName('h2');
        if ($h2Tags->length < 2) {
            $this->errors[] = sprintf(
                'Content must have at least 2 H2 tags for main sections (found: %d)',
                $h2Tags->length
            );
            return false;
        }

        // Check heading hierarchy
        if (!$this->validateHeadingHierarchy($dom)) {
            $this->errors[] = 'Heading hierarchy is invalid (levels should not be skipped)';
            return false;
        }

        // Check paragraph wrapping
        if (!$this->validateParagraphWrapping($dom)) {
            $this->errors[] = 'Text content must be wrapped in paragraph tags';
            return false;
        }

        return true;
    }

    /**
     * Check if content contains Markdown syntax.
     *
     * @param string $content The content to check
     * @return bool True if Markdown syntax is found
     */
    private function containsMarkdownSyntax(string $content): bool
    {
        $markdownPatterns = [
            '/^#{1,6}\s/',           // Headers: # ## ###
            '/\*\*[^*]+\*\*/',       // Bold: **text**
            '/\*[^*]+\*/',           // Italic: *text*
            '/\[[^\]]+\]\([^)]+\)/', // Links: [text](url)
            '/```/',                 // Code blocks: ```
            '/`[^`]+`/',             // Inline code: `code`
        ];

        foreach ($markdownPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if DOM contains inline styles.
     *
     * @param DOMDocument $dom The DOM document
     * @return bool True if inline styles are found
     */
    private function hasInlineStyles(DOMDocument $dom): bool
    {
        $xpath = new \DOMXPath($dom);
        $elementsWithStyle = $xpath->query('//*[@style]');
        
        if ($elementsWithStyle->length > 0) {
            return true;
        }

        // Check for <style> tags
        $styleTags = $dom->getElementsByTagName('style');
        return $styleTags->length > 0;
    }

    /**
     * Check if DOM contains script tags.
     *
     * @param DOMDocument $dom The DOM document
     * @return bool True if script tags are found
     */
    private function hasScripts(DOMDocument $dom): bool
    {
        $scriptTags = $dom->getElementsByTagName('script');
        return $scriptTags->length > 0;
    }

    /**
     * Validate heading hierarchy (no skipped levels).
     *
     * @param DOMDocument $dom The DOM document
     * @return bool True if hierarchy is valid
     */
    private function validateHeadingHierarchy(DOMDocument $dom): bool
    {
        $headings = [];
        
        // Collect all heading tags (h1-h6)
        for ($level = 1; $level <= 6; $level++) {
            $tags = $dom->getElementsByTagName('h' . $level);
            foreach ($tags as $tag) {
                $headings[] = $level;
            }
        }

        if (empty($headings)) {
            return true;
        }

        // Check for skipped levels
        $previousLevel = 0;
        foreach ($headings as $level) {
            if ($previousLevel > 0 && $level > $previousLevel + 1) {
                // Skipped a level (e.g., h2 directly to h4)
                return false;
            }
            $previousLevel = $level;
        }

        return true;
    }

    /**
     * Validate that text content is wrapped in paragraph tags.
     *
     * @param DOMDocument $dom The DOM document
     * @return bool True if text is properly wrapped
     */
    private function validateParagraphWrapping(DOMDocument $dom): bool
    {
        $xpath = new \DOMXPath($dom);
        
        // Find text nodes that are direct children of body or other block elements
        // (excluding text within proper tags like p, h2, h3, etc.)
        $textNodes = $xpath->query('//text()[normalize-space(.) != ""]');
        
        foreach ($textNodes as $textNode) {
            $parent = $textNode->parentNode;
            
            // Skip if parent is already a proper text container
            $validParents = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'td', 'th', 'blockquote', 'figcaption'];
            if (in_array(strtolower($parent->nodeName), $validParents)) {
                continue;
            }

            // Check if this is significant text (not just whitespace)
            $text = trim($textNode->nodeValue);
            if (strlen($text) > 20) { // Significant text should be wrapped
                return false;
            }
        }

        return true;
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
        return 'HTMLStructureValidator';
    }
}
