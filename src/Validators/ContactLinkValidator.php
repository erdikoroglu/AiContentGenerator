<?php

namespace ErdiKoroglu\AIContentGenerator\Validators;

use DOMDocument;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;

/**
 * Contact Link Validator
 * 
 * Validates contact link requirements:
 * - Exactly one contact link
 * - target="_blank" and rel="nofollow" attributes
 * - No social media links
 * 
 * Requirements: 15.1, 15.4, 15.5, 15.6, 15.7, 16.4, 16.5
 */
class ContactLinkValidator implements ValidatorInterface
{
    private array $errors = [];

    /**
     * Social media domains to reject
     */
    private const SOCIAL_MEDIA_DOMAINS = [
        'facebook.com',
        'twitter.com',
        'instagram.com',
        'linkedin.com',
        'youtube.com',
        'tiktok.com',
        'pinterest.com',
        'snapchat.com',
        'reddit.com',
        'tumblr.com',
        'x.com',
    ];

    /**
     * Validate contact link requirements.
     *
     * @param string $content The HTML content to validate
     * @param ContentRequest $request The original content generation request
     * @return bool True if validation passes, false otherwise
     */
    public function validate(string $content, ContentRequest $request): bool
    {
        $this->errors = [];

        // Parse HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Get all anchor tags
        $links = $dom->getElementsByTagName('a');
        
        $contactLinkCount = 0;
        $contactUrl = $request->contactUrl;
        $contactLinkHasCorrectAttributes = false;
        $linksWithTargetBlank = [];
        $linksWithNofollow = [];

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            
            // Check for social media links
            if ($this->isSocialMediaLink($href)) {
                $this->errors[] = sprintf(
                    'Content contains social media link: %s',
                    $href
                );
                return false;
            }

            // Check if this is the contact link
            if ($this->normalizeUrl($href) === $this->normalizeUrl($contactUrl)) {
                $contactLinkCount++;
                
                // Check attributes
                $hasTargetBlank = $link->getAttribute('target') === '_blank';
                $hasNofollow = str_contains($link->getAttribute('rel'), 'nofollow');
                
                if ($hasTargetBlank && $hasNofollow) {
                    $contactLinkHasCorrectAttributes = true;
                }
            }

            // Track links with target="_blank"
            if ($link->getAttribute('target') === '_blank') {
                $linksWithTargetBlank[] = $href;
            }

            // Track links with rel="nofollow"
            if (str_contains($link->getAttribute('rel'), 'nofollow')) {
                $linksWithNofollow[] = $href;
            }
        }

        // Validate exactly one contact link
        if ($contactLinkCount === 0) {
            $this->errors[] = 'Content must contain exactly one contact link';
            return false;
        }

        if ($contactLinkCount > 1) {
            $this->errors[] = sprintf(
                'Content must contain exactly one contact link (found: %d)',
                $contactLinkCount
            );
            return false;
        }

        // Validate contact link has correct attributes
        if (!$contactLinkHasCorrectAttributes) {
            $this->errors[] = 'Contact link must have target="_blank" and rel="nofollow" attributes';
            return false;
        }

        // Validate only contact link has target="_blank"
        if (count($linksWithTargetBlank) > 1) {
            $this->errors[] = 'Only the contact link should have target="_blank" attribute';
            return false;
        }

        // Validate only contact link has rel="nofollow"
        if (count($linksWithNofollow) > 1) {
            $this->errors[] = 'Only the contact link should have rel="nofollow" attribute';
            return false;
        }

        return true;
    }

    /**
     * Check if URL is a social media link.
     *
     * @param string $url The URL to check
     * @return bool True if URL is a social media link
     */
    private function isSocialMediaLink(string $url): bool
    {
        $url = strtolower($url);
        
        foreach (self::SOCIAL_MEDIA_DOMAINS as $domain) {
            if (str_contains($url, $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize URL for comparison.
     *
     * @param string $url The URL to normalize
     * @return string Normalized URL
     */
    private function normalizeUrl(string $url): string
    {
        // Remove protocol
        $url = preg_replace('#^https?://#i', '', $url);
        
        // Remove www.
        $url = preg_replace('#^www\.#i', '', $url);
        
        // Remove trailing slash
        $url = rtrim($url, '/');
        
        return strtolower($url);
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
        return 'ContactLinkValidator';
    }
}
