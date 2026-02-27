<?php

use ErdiKoroglu\AIContentGenerator\Tests\Factories\ContentRequestFactory;
use ErdiKoroglu\AIContentGenerator\Tests\Factories\LocaleConfigurationFactory;
use ErdiKoroglu\AIContentGenerator\Tests\Factories\AuthorPersonaFactory;
use ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest;
use ErdiKoroglu\AIContentGenerator\DTOs\LocaleConfiguration;
use ErdiKoroglu\AIContentGenerator\Models\AuthorPersona;

describe('ContentRequestFactory', function () {
    test('creates content request with default values', function () {
        $request = ContentRequestFactory::make();
        
        expect($request)->toBeInstanceOf(ContentRequest::class)
            ->and($request->focusKeyword)->toBeString()
            ->and($request->relatedKeywords)->toBeArray()
            ->and($request->searchIntent)->toBeIn(['informational', 'navigational', 'transactional', 'commercial'])
            ->and($request->contentType)->toBeIn(['how-to', 'concept', 'news'])
            ->and($request->locale)->toBeInstanceOf(LocaleConfiguration::class)
            ->and($request->author)->toBeInstanceOf(AuthorPersona::class)
            ->and($request->wordCountMin)->toBeGreaterThan(0)
            ->and($request->wordCountMax)->toBeGreaterThan($request->wordCountMin)
            ->and($request->contactUrl)->toBeString();
    });

    test('creates content request with overrides', function () {
        $request = ContentRequestFactory::make([
            'focusKeyword' => 'Laravel Testing',
            'wordCountMin' => 1000,
            'wordCountMax' => 2000,
        ]);
        
        expect($request->focusKeyword)->toBe('Laravel Testing')
            ->and($request->wordCountMin)->toBe(1000)
            ->and($request->wordCountMax)->toBe(2000);
    });

    test('creates how-to content request', function () {
        $request = ContentRequestFactory::howTo();
        
        expect($request->contentType)->toBe('how-to');
    });

    test('creates concept content request', function () {
        $request = ContentRequestFactory::concept();
        
        expect($request->contentType)->toBe('concept');
    });

    test('creates news content request', function () {
        $request = ContentRequestFactory::news();
        
        expect($request->contentType)->toBe('news');
    });

    test('creates informational content request', function () {
        $request = ContentRequestFactory::informational();
        
        expect($request->searchIntent)->toBe('informational');
    });

    test('creates transactional content request', function () {
        $request = ContentRequestFactory::transactional();
        
        expect($request->searchIntent)->toBe('transactional');
    });

    test('creates content request with specific word count', function () {
        $request = ContentRequestFactory::withWordCount(800, 1200);
        
        expect($request->wordCountMin)->toBe(800)
            ->and($request->wordCountMax)->toBe(1200);
    });

    test('creates content request with specific keyword', function () {
        $request = ContentRequestFactory::withKeyword('PHP Development');
        
        expect($request->focusKeyword)->toBe('PHP Development');
    });
});

describe('LocaleConfigurationFactory', function () {
    test('creates locale configuration with default values', function () {
        $locale = LocaleConfigurationFactory::make();
        
        expect($locale)->toBeInstanceOf(LocaleConfiguration::class)
            ->and($locale->locale)->toBe('en_US')
            ->and($locale->localeName)->toBe('English')
            ->and($locale->targetCountry)->toBe('US')
            ->and($locale->currency)->toBe('USD');
    });

    test('creates random locale configuration', function () {
        $locale = LocaleConfigurationFactory::random();
        
        expect($locale)->toBeInstanceOf(LocaleConfiguration::class)
            ->and($locale->locale)->toBeString()
            ->and($locale->localeName)->toBeString()
            ->and($locale->targetCountry)->toBeString()
            ->and($locale->currency)->toBeString();
    });

    test('creates US English locale', function () {
        $locale = LocaleConfigurationFactory::enUS();
        
        expect($locale->locale)->toBe('en_US')
            ->and($locale->currency)->toBe('USD');
    });

    test('creates Turkish locale', function () {
        $locale = LocaleConfigurationFactory::trTR();
        
        expect($locale->locale)->toBe('tr_TR')
            ->and($locale->localeName)->toBe('Türkçe')
            ->and($locale->currency)->toBe('TRY');
    });

    test('creates German locale', function () {
        $locale = LocaleConfigurationFactory::deDE();
        
        expect($locale->locale)->toBe('de_DE')
            ->and($locale->currency)->toBe('EUR');
    });

    test('lists available locales', function () {
        $locales = LocaleConfigurationFactory::availableLocales();
        
        expect($locales)->toBeArray()
            ->and($locales)->toContain('en_US', 'tr_TR', 'de_DE', 'fr_FR');
    });
});

describe('AuthorPersonaFactory', function () {
    test('creates author persona with default values', function () {
        $author = AuthorPersonaFactory::make();
        
        expect($author)->toBeInstanceOf(AuthorPersona::class)
            ->and($author->author_name)->toBeString()
            ->and($author->author_company)->toBeString()
            ->and($author->author_job_title)->toBeString()
            ->and($author->author_expertise_areas)->toBeArray()
            ->and($author->author_expertise_areas)->not->toBeEmpty()
            ->and($author->author_short_bio)->toBeString()
            ->and(strlen($author->author_short_bio))->toBeLessThanOrEqual(500)
            ->and($author->author_url)->toBeString();
    });

    test('creates technology-focused author', function () {
        $author = AuthorPersonaFactory::technology();
        
        expect($author->author_expertise_areas)->toBeArray()
            ->and($author->author_job_title)->toBeString();
    });

    test('creates business-focused author', function () {
        $author = AuthorPersonaFactory::business();
        
        expect($author->author_expertise_areas)->toBeArray()
            ->and($author->author_job_title)->toBeString();
    });

    test('creates design-focused author', function () {
        $author = AuthorPersonaFactory::design();
        
        expect($author->author_expertise_areas)->toBeArray()
            ->and($author->author_job_title)->toBeString();
    });

    test('creates content-focused author', function () {
        $author = AuthorPersonaFactory::content();
        
        expect($author->author_expertise_areas)->toBeArray()
            ->and($author->author_job_title)->toBeString();
    });

    test('creates author with specific expertise', function () {
        $expertise = ['Laravel', 'PHP', 'Testing'];
        $author = AuthorPersonaFactory::withExpertise($expertise);
        
        expect($author->author_expertise_areas)->toBe($expertise);
    });

    test('creates author with specific name', function () {
        $author = AuthorPersonaFactory::withName('John Doe');
        
        expect($author->author_name)->toBe('John Doe');
    });

    test('creates author with specific company', function () {
        $author = AuthorPersonaFactory::withCompany('Acme Corp');
        
        expect($author->author_company)->toBe('Acme Corp');
    });

    test('bio is under 500 characters', function () {
        $author = AuthorPersonaFactory::make();
        
        expect(strlen($author->author_short_bio))->toBeLessThanOrEqual(500);
    });
});
