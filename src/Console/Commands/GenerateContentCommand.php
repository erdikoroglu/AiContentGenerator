<?php

namespace ErdiKoroglu\AIContentGenerator\Console\Commands;

use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;
use ErdiKoroglu\AIContentGenerator\DTOs\LocaleConfiguration;
use ErdiKoroglu\AIContentGenerator\Models\AuthorPersona;
use ErdiKoroglu\AIContentGenerator\Services\ContentGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Generate Content Command
 * 
 * Artisan command for generating AI content from the CLI.
 * 
 * Usage:
 *   php artisan ai-content:generate "Laravel Tutorial" --related-keywords="PHP,Framework" --output=content.json
 */
class GenerateContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-content:generate
                            {focus-keyword : The main keyword for the content}
                            {--related-keywords= : Comma-separated related keywords}
                            {--search-intent=informational : Search intent (informational, transactional, navigational, commercial)}
                            {--content-type=concept : Content type (how-to, concept, news)}
                            {--locale=en_US : Locale code (e.g., en_US, tr_TR, de_DE)}
                            {--author-id= : Author persona ID from database}
                            {--word-count-min=800 : Minimum word count}
                            {--word-count-max=1500 : Maximum word count}
                            {--intro-word-count=100 : Introduction word count}
                            {--conclusion-word-count=100 : Conclusion word count}
                            {--main-content-word-count=600 : Main content word count}
                            {--faq-count=3 : Minimum FAQ count}
                            {--contact-url= : Contact URL (required)}
                            {--ai-provider= : Override AI provider}
                            {--image-provider= : Override image provider}
                            {--output= : Output file path (if not specified, output to stdout)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate AI-powered SEO content from the command line';

    /**
     * Content generator service
     *
     * @var ContentGeneratorService
     */
    protected ContentGeneratorService $generator;

    /**
     * Create a new command instance.
     *
     * @param ContentGeneratorService $generator
     */
    public function __construct(ContentGeneratorService $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            // Validate required parameters
            if (!$this->validateParameters()) {
                return Command::FAILURE;
            }

            // Build content request
            $request = $this->buildContentRequest();

            // Display generation info
            $this->displayGenerationInfo($request);

            // Generate content with progress indication
            $this->info('Generating content...');
            $response = $this->generator->generate($request);

            // Output result
            $this->outputResult($response);

            $this->newLine();
            $this->info('✓ Content generated successfully!');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error generating content: ' . $e->getMessage());
            
            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }

    /**
     * Validate command parameters
     *
     * @return bool
     */
    protected function validateParameters(): bool
    {
        // Validate contact URL
        if (!$this->option('contact-url')) {
            $this->error('The --contact-url option is required.');
            return false;
        }

        if (!filter_var($this->option('contact-url'), FILTER_VALIDATE_URL)) {
            $this->error('The --contact-url must be a valid URL.');
            return false;
        }

        // Validate search intent
        $validIntents = ['informational', 'transactional', 'navigational', 'commercial'];
        if (!in_array($this->option('search-intent'), $validIntents)) {
            $this->error('Invalid search intent. Must be one of: ' . implode(', ', $validIntents));
            return false;
        }

        // Validate content type
        $validTypes = ['how-to', 'concept', 'news'];
        if (!in_array($this->option('content-type'), $validTypes)) {
            $this->error('Invalid content type. Must be one of: ' . implode(', ', $validTypes));
            return false;
        }

        // Validate word counts
        $minCount = (int) $this->option('word-count-min');
        $maxCount = (int) $this->option('word-count-max');
        
        if ($minCount >= $maxCount) {
            $this->error('Minimum word count must be less than maximum word count.');
            return false;
        }

        if ($minCount < 100 || $maxCount > 10000) {
            $this->error('Word count must be between 100 and 10000.');
            return false;
        }

        // Validate FAQ count
        $faqCount = (int) $this->option('faq-count');
        if ($faqCount < 3) {
            $this->error('FAQ count must be at least 3.');
            return false;
        }

        return true;
    }

    /**
     * Build content request from command options
     *
     * @return ContentRequest
     */
    protected function buildContentRequest(): ContentRequest
    {
        // Get or create author persona
        $author = $this->getAuthorPersona();

        // Parse related keywords
        $relatedKeywords = $this->option('related-keywords')
            ? array_map('trim', explode(',', $this->option('related-keywords')))
            : [];

        // Build locale configuration
        $locale = $this->buildLocaleConfiguration();

        return new ContentRequest(
            focusKeyword: $this->argument('focus-keyword'),
            relatedKeywords: $relatedKeywords,
            searchIntent: $this->option('search-intent'),
            contentType: $this->option('content-type'),
            locale: $locale,
            author: $author,
            wordCountMin: (int) $this->option('word-count-min'),
            wordCountMax: (int) $this->option('word-count-max'),
            introWordCount: (int) $this->option('intro-word-count'),
            conclusionWordCount: (int) $this->option('conclusion-word-count'),
            mainContentWordCount: (int) $this->option('main-content-word-count'),
            faqMinCount: (int) $this->option('faq-count'),
            contactUrl: $this->option('contact-url'),
            aiProvider: $this->option('ai-provider'),
            imageProvider: $this->option('image-provider')
        );
    }

    /**
     * Get author persona from database or create default
     *
     * @return AuthorPersona
     */
    protected function getAuthorPersona(): AuthorPersona
    {
        $authorId = $this->option('author-id');

        if ($authorId) {
            $author = AuthorPersona::find($authorId);
            
            if (!$author) {
                $this->warn("Author persona with ID {$authorId} not found. Using default author.");
                return $this->createDefaultAuthor();
            }
            
            return $author;
        }

        return $this->createDefaultAuthor();
    }

    /**
     * Create default author persona
     *
     * @return AuthorPersona
     */
    protected function createDefaultAuthor(): AuthorPersona
    {
        $author = new AuthorPersona();
        $author->author_name = config('ai-content-generator.defaults.author_name', 'Content Writer');
        $author->author_company = config('ai-content-generator.defaults.author_company');
        $author->author_job_title = config('ai-content-generator.defaults.author_job_title', 'Content Specialist');
        $author->author_expertise_areas = config('ai-content-generator.defaults.author_expertise_areas', ['Content Writing', 'SEO']);
        $author->author_short_bio = config('ai-content-generator.defaults.author_short_bio', 'Experienced content writer specializing in SEO-optimized articles.');
        $author->author_url = config('ai-content-generator.defaults.author_url');
        
        return $author;
    }

    /**
     * Build locale configuration from option
     *
     * @return LocaleConfiguration
     */
    protected function buildLocaleConfiguration(): LocaleConfiguration
    {
        $localeCode = $this->option('locale');
        
        // Map common locales to their configurations
        $localeMap = [
            'en_US' => ['name' => 'English', 'country' => 'US', 'currency' => 'USD'],
            'tr_TR' => ['name' => 'Türkçe', 'country' => 'TR', 'currency' => 'TRY'],
            'de_DE' => ['name' => 'Deutsch', 'country' => 'DE', 'currency' => 'EUR'],
            'fr_FR' => ['name' => 'Français', 'country' => 'FR', 'currency' => 'EUR'],
            'es_ES' => ['name' => 'Español', 'country' => 'ES', 'currency' => 'EUR'],
            'it_IT' => ['name' => 'Italiano', 'country' => 'IT', 'currency' => 'EUR'],
            'pt_BR' => ['name' => 'Português', 'country' => 'BR', 'currency' => 'BRL'],
            'ja_JP' => ['name' => '日本語', 'country' => 'JP', 'currency' => 'JPY'],
            'zh_CN' => ['name' => '中文', 'country' => 'CN', 'currency' => 'CNY'],
        ];

        $config = $localeMap[$localeCode] ?? [
            'name' => 'English',
            'country' => 'US',
            'currency' => 'USD'
        ];

        return new LocaleConfiguration(
            locale: $localeCode,
            localeName: $config['name'],
            targetCountry: $config['country'],
            currency: $config['currency']
        );
    }

    /**
     * Display generation information
     *
     * @param ContentRequest $request
     * @return void
     */
    protected function displayGenerationInfo(ContentRequest $request): void
    {
        $this->newLine();
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════════</>');
        $this->line('<fg=cyan>  AI Content Generator</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════════</>');
        $this->newLine();
        
        $this->line("<fg=yellow>Focus Keyword:</> {$request->focusKeyword}");
        
        if (!empty($request->relatedKeywords)) {
            $this->line("<fg=yellow>Related Keywords:</> " . implode(', ', $request->relatedKeywords));
        }
        
        $this->line("<fg=yellow>Search Intent:</> {$request->searchIntent}");
        $this->line("<fg=yellow>Content Type:</> {$request->contentType}");
        $this->line("<fg=yellow>Locale:</> {$request->locale->locale} ({$request->locale->localeName})");
        $this->line("<fg=yellow>Word Count:</> {$request->wordCountMin} - {$request->wordCountMax}");
        $this->line("<fg=yellow>Author:</> {$request->author->author_name}");
        
        if ($request->aiProvider) {
            $this->line("<fg=yellow>AI Provider:</> {$request->aiProvider}");
        }
        
        if ($request->imageProvider) {
            $this->line("<fg=yellow>Image Provider:</> {$request->imageProvider}");
        }
        
        $this->newLine();
    }

    /**
     * Output result to file or stdout
     *
     * @param \ErdiKoroglu\AIContentGenerator\DTOs\ContentResponse $response
     * @return void
     */
    protected function outputResult($response): void
    {
        $json = $response->toJson();
        $outputPath = $this->option('output');

        if ($outputPath) {
            // Output to file
            File::put($outputPath, $json);
            $this->newLine();
            $this->info("Content saved to: {$outputPath}");
            
            // Display summary
            $this->displaySummary($response);
        } else {
            // Output to stdout
            $this->newLine();
            $this->line($json);
        }
    }

    /**
     * Display content summary
     *
     * @param \ErdiKoroglu\AIContentGenerator\DTOs\ContentResponse $response
     * @return void
     */
    protected function displaySummary($response): void
    {
        $this->newLine();
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════════</>');
        $this->line('<fg=cyan>  Content Summary</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════════</>');
        $this->newLine();
        
        $this->line("<fg=green>Title:</> {$response->title}");
        $this->line("<fg=green>Word Count:</> {$response->wordCount}");
        $this->line("<fg=green>FAQs:</> " . count($response->faqs));
        $this->line("<fg=green>Images:</> " . count($response->images));
        $this->line("<fg=green>Generated At:</> {$response->generatedAt->format('Y-m-d H:i:s')}");
        
        $this->newLine();
        $this->line("<fg=yellow>Meta Description:</>");
        $this->line($response->metaDescription);
        
        $this->newLine();
        $this->line("<fg=yellow>Excerpt:</>");
        $this->line($response->excerpt);
    }
}
