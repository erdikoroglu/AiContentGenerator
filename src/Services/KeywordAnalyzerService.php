<?php

namespace ErdiKoroglu\AIContentGenerator\Services;

/**
 * Keyword Analyzer Service
 * 
 * Provides keyword analysis functionality for SEO optimization including:
 * - Keyword density calculation
 * - Keyword distribution analysis
 * - Search intent validation
 * 
 * Requirements: 6.4, 6.10
 */
class KeywordAnalyzerService
{
    /**
     * Calculate keyword density as a percentage.
     * 
     * Strips HTML tags and calculates the percentage of times the keyword
     * appears in the content relative to total word count.
     * Uses case-insensitive matching.
     *
     * @param string $content The HTML content to analyze
     * @param string $keyword The keyword to search for
     * @return float The keyword density as a percentage (e.g., 1.5 for 1.5%)
     */
    public function calculateDensity(string $content, string $keyword): float
    {
        // Strip HTML tags to get plain text
        $plainText = strip_tags($content);
        
        // Calculate total word count
        $words = preg_split('/\s+/', trim($plainText), -1, PREG_SPLIT_NO_EMPTY);
        $totalWords = count($words);

        if ($totalWords === 0) {
            return 0.0;
        }

        // Count keyword occurrences (case-insensitive, whole word match)
        $keywordPattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
        preg_match_all($keywordPattern, $plainText, $matches);
        $keywordCount = count($matches[0]);

        // Calculate density as percentage
        return ($keywordCount / $totalWords) * 100;
    }

    /**
     * Analyze keyword distribution throughout the content.
     * 
     * Divides content into three sections (intro, body, conclusion) and
     * calculates keyword occurrences in each section.
     *
     * @param string $content The HTML content to analyze
     * @param string $keyword The keyword to search for
     * @return array Array with keys 'intro', 'body', 'conclusion' containing occurrence counts
     */
    public function analyzeKeywordDistribution(string $content, string $keyword): array
    {
        // Strip HTML tags to get plain text
        $plainText = strip_tags($content);
        
        // Split content into words
        $words = preg_split('/\s+/', trim($plainText), -1, PREG_SPLIT_NO_EMPTY);
        $totalWords = count($words);

        if ($totalWords === 0) {
            return [
                'intro' => 0,
                'body' => 0,
                'conclusion' => 0,
            ];
        }

        // Define section boundaries (approximate)
        // Intro: first 15% of content
        // Body: middle 70% of content
        // Conclusion: last 15% of content
        $introEnd = (int) ($totalWords * 0.15);
        $bodyEnd = (int) ($totalWords * 0.85);

        // Extract sections
        $introWords = array_slice($words, 0, $introEnd);
        $bodyWords = array_slice($words, $introEnd, $bodyEnd - $introEnd);
        $conclusionWords = array_slice($words, $bodyEnd);

        // Reconstruct text for each section
        $introText = implode(' ', $introWords);
        $bodyText = implode(' ', $bodyWords);
        $conclusionText = implode(' ', $conclusionWords);

        // Count keyword occurrences in each section (case-insensitive)
        $keywordPattern = '/\b' . preg_quote($keyword, '/') . '\b/i';

        preg_match_all($keywordPattern, $introText, $introMatches);
        preg_match_all($keywordPattern, $bodyText, $bodyMatches);
        preg_match_all($keywordPattern, $conclusionText, $conclusionMatches);

        return [
            'intro' => count($introMatches[0]),
            'body' => count($bodyMatches[0]),
            'conclusion' => count($conclusionMatches[0]),
        ];
    }

    /**
     * Validate that content matches the specified search intent.
     * 
     * Analyzes content for patterns and keywords that indicate alignment
     * with the search intent (informational, transactional, navigational).
     *
     * @param string $content The HTML content to validate
     * @param string $searchIntent The expected search intent (informational, transactional, navigational)
     * @return bool True if content matches the search intent, false otherwise
     */
    public function validateSearchIntent(string $content, string $searchIntent): bool
    {
        // Strip HTML tags to get plain text
        $plainText = strtolower(strip_tags($content));

        // Define intent-specific patterns and keywords
        $intentPatterns = [
            'informational' => [
                'keywords' => ['what', 'how', 'why', 'guide', 'tutorial', 'learn', 'understand', 'explain', 'definition', 'meaning'],
                'patterns' => ['/\bhow to\b/', '/\bwhat is\b/', '/\bwhy does\b/', '/\bguide to\b/'],
            ],
            'transactional' => [
                'keywords' => ['buy', 'purchase', 'order', 'price', 'cost', 'discount', 'deal', 'shop', 'cart', 'checkout'],
                'patterns' => ['/\bbuy now\b/', '/\badd to cart\b/', '/\bget started\b/', '/\bsign up\b/'],
            ],
            'navigational' => [
                'keywords' => ['login', 'sign in', 'account', 'dashboard', 'portal', 'official', 'website', 'homepage'],
                'patterns' => ['/\bofficial site\b/', '/\blog in\b/', '/\bsign in\b/', '/\baccess\b/'],
            ],
        ];

        // Normalize search intent
        $searchIntent = strtolower($searchIntent);

        // If intent not recognized, return true (no validation)
        if (!isset($intentPatterns[$searchIntent])) {
            return true;
        }

        $patterns = $intentPatterns[$searchIntent];
        $matchCount = 0;

        // Check for keyword matches
        foreach ($patterns['keywords'] as $keyword) {
            if (strpos($plainText, $keyword) !== false) {
                $matchCount++;
            }
        }

        // Check for pattern matches
        foreach ($patterns['patterns'] as $pattern) {
            if (preg_match($pattern, $plainText)) {
                $matchCount++;
            }
        }

        // Content should have at least 2 matches to be considered aligned with intent
        return $matchCount >= 2;
    }
}
