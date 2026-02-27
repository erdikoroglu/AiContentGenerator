<?php

use ErdiKoroglu\AIContentGenerator\Services\KeywordAnalyzerService;

describe('KeywordAnalyzerService', function () {
    describe('calculateDensity', function () {
        it('calculates keyword density correctly', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<p>Laravel is great. Laravel is awesome. Laravel is powerful. ' .
                      'This is a test with 20 words total including Laravel mentions.</p>';
            $keyword = 'Laravel';

            $density = $analyzer->calculateDensity($content, $keyword);

            // 4 occurrences of "Laravel" in 20 words = 20%
            expect($density)->toBe(20.0);
        });

        it('is case-insensitive', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<p>Laravel is great. LARAVEL is awesome. laravel is powerful.</p>';
            $keyword = 'Laravel';

            $density = $analyzer->calculateDensity($content, $keyword);

            // 3 occurrences in 9 words = 33.33%
            expect($density)->toBeGreaterThan(33.0)
                ->toBeLessThan(34.0);
        });

        it('strips HTML tags before analysis', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<h2>Laravel Tutorial</h2><p>Learn <strong>Laravel</strong> framework.</p>';
            $keyword = 'Laravel';

            $density = $analyzer->calculateDensity($content, $keyword);

            // 2 occurrences in 4 words = 50%
            expect($density)->toBe(50.0);
        });

        it('matches whole words only', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<p>Laravel Laraveling Laravels framework</p>';
            $keyword = 'Laravel';

            $density = $analyzer->calculateDensity($content, $keyword);

            // Only 1 exact match in 4 words = 25%
            expect($density)->toBe(25.0);
        });

        it('returns 0 for empty content', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '';
            $keyword = 'Laravel';

            $density = $analyzer->calculateDensity($content, $keyword);

            expect($density)->toBe(0.0);
        });

        it('returns 0 when keyword not found', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<p>This is some content without the target word.</p>';
            $keyword = 'Laravel';

            $density = $analyzer->calculateDensity($content, $keyword);

            expect($density)->toBe(0.0);
        });

        it('handles multi-word keywords', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<p>Content marketing is important. Content marketing strategies work. ' .
                      'Learn content marketing today.</p>';
            $keyword = 'content marketing';

            $density = $analyzer->calculateDensity($content, $keyword);

            // 3 occurrences in 12 words = 25%
            expect($density)->toBe(25.0);
        });
    });

    describe('analyzeKeywordDistribution', function () {
        it('analyzes keyword distribution across sections', function () {
            $analyzer = new KeywordAnalyzerService();
            // Create content with known distribution
            // 100 words total: 15 intro, 70 body, 15 conclusion
            $intro = str_repeat('Laravel intro word ', 15); // 1 keyword in intro
            $body = str_repeat('Laravel body word ', 35); // 35 keywords in body
            $conclusion = str_repeat('conclusion word test ', 15); // 0 keywords in conclusion

            $content = '<p>' . $intro . $body . $conclusion . '</p>';
            $keyword = 'Laravel';

            $distribution = $analyzer->analyzeKeywordDistribution($content, $keyword);

            expect($distribution)->toHaveKey('intro')
                ->toHaveKey('body')
                ->toHaveKey('conclusion');

            // Intro should have some occurrences
            expect($distribution['intro'])->toBeGreaterThan(0);
            
            // Body should have the most occurrences
            expect($distribution['body'])->toBeGreaterThan($distribution['intro']);
            
            // Conclusion should have 0
            expect($distribution['conclusion'])->toBe(0);
        });

        it('returns zeros for empty content', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '';
            $keyword = 'Laravel';

            $distribution = $analyzer->analyzeKeywordDistribution($content, $keyword);

            expect($distribution)->toBe([
                'intro' => 0,
                'body' => 0,
                'conclusion' => 0,
            ]);
        });

        it('is case-insensitive', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<p>' . str_repeat('LARAVEL word ', 50) . '</p>';
            $keyword = 'laravel';

            $distribution = $analyzer->analyzeKeywordDistribution($content, $keyword);

            $total = $distribution['intro'] + $distribution['body'] + $distribution['conclusion'];
            expect($total)->toBe(50);
        });

        it('strips HTML tags before analysis', function () {
            $analyzer = new KeywordAnalyzerService();
            // Create longer content so distribution works properly
            $content = '<h2>Laravel Tutorial</h2>' . 
                      str_repeat('<p>Laravel is a framework for web development. </p>', 10) .
                      '<p>Learn Laravel today.</p>';
            $keyword = 'Laravel';

            $distribution = $analyzer->analyzeKeywordDistribution($content, $keyword);

            $total = $distribution['intro'] + $distribution['body'] + $distribution['conclusion'];
            // Should count all Laravel occurrences (11-12 depending on parsing)
            expect($total)->toBeGreaterThanOrEqual(11);
        });
    });

    describe('validateSearchIntent', function () {
        it('validates informational intent', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<h2>What is Laravel?</h2>' .
                      '<p>This guide will help you learn and understand Laravel framework.</p>';
            $searchIntent = 'informational';

            $result = $analyzer->validateSearchIntent($content, $searchIntent);

            expect($result)->toBeTrue();
        });

        it('validates transactional intent', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<h2>Buy Laravel Course</h2>' .
                      '<p>Purchase our Laravel course now. Add to cart and get started today.</p>';
            $searchIntent = 'transactional';

            $result = $analyzer->validateSearchIntent($content, $searchIntent);

            expect($result)->toBeTrue();
        });

        it('validates navigational intent', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<h2>Laravel Official Website</h2>' .
                      '<p>Login to your Laravel account and access the dashboard.</p>';
            $searchIntent = 'navigational';

            $result = $analyzer->validateSearchIntent($content, $searchIntent);

            expect($result)->toBeTrue();
        });

        it('rejects mismatched intent', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<h2>Buy Laravel Course</h2>' .
                      '<p>Purchase our Laravel course now.</p>';
            $searchIntent = 'informational';

            $result = $analyzer->validateSearchIntent($content, $searchIntent);

            expect($result)->toBeFalse();
        });

        it('is case-insensitive', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<h2>WHAT IS LARAVEL?</h2>' .
                      '<p>THIS GUIDE WILL HELP YOU LEARN LARAVEL.</p>';
            $searchIntent = 'informational';

            $result = $analyzer->validateSearchIntent($content, $searchIntent);

            expect($result)->toBeTrue();
        });

        it('strips HTML tags before validation', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<h2>What is <strong>Laravel</strong>?</h2>' .
                      '<p>Learn <em>how to</em> use Laravel.</p>';
            $searchIntent = 'informational';

            $result = $analyzer->validateSearchIntent($content, $searchIntent);

            expect($result)->toBeTrue();
        });

        it('returns true for unrecognized intent', function () {
            $analyzer = new KeywordAnalyzerService();
            $content = '<p>Some content here.</p>';
            $searchIntent = 'unknown-intent';

            $result = $analyzer->validateSearchIntent($content, $searchIntent);

            expect($result)->toBeTrue();
        });

        it('requires at least 2 matches for validation', function () {
            $analyzer = new KeywordAnalyzerService();
            // Only 1 informational keyword
            $content = '<p>This is a guide about something.</p>';
            $searchIntent = 'informational';

            $result = $analyzer->validateSearchIntent($content, $searchIntent);

            expect($result)->toBeFalse();
        });
    });
});
