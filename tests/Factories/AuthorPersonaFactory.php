<?php

declare(strict_types=1);

namespace ErdiKoroglu\AIContentGenerator\Tests\Factories;

use ErdiKoroglu\AIContentGenerator\Models\AuthorPersona;
use Faker\Factory as FakerFactory;
use Faker\Generator;

/**
 * Author Persona Factory
 *
 * Factory for generating AuthorPersona instances with sensible defaults
 * and realistic data for testing purposes.
 *
 * @package ErdiKoroglu\AIContentGenerator\Tests\Factories
 */
class AuthorPersonaFactory
{
    private static ?Generator $faker = null;

    /**
     * Predefined expertise areas for realistic author personas
     *
     * @var array<string, array<int, string>>
     */
    private static array $expertiseByField = [
        'technology' => [
            'Software Development',
            'Web Development',
            'Mobile App Development',
            'Cloud Computing',
            'DevOps',
            'Cybersecurity',
            'Artificial Intelligence',
            'Machine Learning',
            'Data Science',
        ],
        'business' => [
            'Business Strategy',
            'Marketing',
            'Sales',
            'Project Management',
            'Leadership',
            'Entrepreneurship',
            'Finance',
            'Human Resources',
        ],
        'design' => [
            'UI/UX Design',
            'Graphic Design',
            'Web Design',
            'Product Design',
            'Brand Design',
            'Motion Graphics',
        ],
        'content' => [
            'Content Writing',
            'Copywriting',
            'SEO',
            'Content Strategy',
            'Technical Writing',
            'Blogging',
        ],
    ];

    /**
     * Get Faker instance
     *
     * @return Generator
     */
    private static function faker(): Generator
    {
        if (self::$faker === null) {
            self::$faker = FakerFactory::create();
        }

        return self::$faker;
    }

    /**
     * Create an AuthorPersona with default values
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return AuthorPersona
     */
    public static function make(array $overrides = []): AuthorPersona
    {
        $faker = self::faker();

        // Select a random field and get expertise areas
        $field = $faker->randomElement(array_keys(self::$expertiseByField));
        $allExpertise = self::$expertiseByField[$field];
        $expertiseCount = $faker->numberBetween(2, 4);
        $expertise = $faker->randomElements($allExpertise, $expertiseCount);

        $name = $overrides['author_name'] ?? $faker->name();
        $company = $overrides['author_company'] ?? $faker->company();
        $jobTitle = $overrides['author_job_title'] ?? self::getJobTitle($field);

        $author = new AuthorPersona();
        $author->author_name = $name;
        $author->author_company = $company;
        $author->author_job_title = $jobTitle;
        $author->author_expertise_areas = $overrides['author_expertise_areas'] ?? $expertise;
        $author->author_short_bio = $overrides['author_short_bio'] ?? self::generateBio($name, $jobTitle, $company);
        $author->author_url = $overrides['author_url'] ?? $faker->url();

        return $author;
    }

    /**
     * Create an AuthorPersona with random values
     *
     * Alias for make() for better readability in tests
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return AuthorPersona
     */
    public static function random(array $overrides = []): AuthorPersona
    {
        return self::make($overrides);
    }

    /**
     * Create a technology-focused AuthorPersona
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return AuthorPersona
     */
    public static function technology(array $overrides = []): AuthorPersona
    {
        $faker = self::faker();
        $expertise = $faker->randomElements(self::$expertiseByField['technology'], $faker->numberBetween(2, 4));

        return self::make(array_merge([
            'author_expertise_areas' => $expertise,
            'author_job_title' => $faker->randomElement([
                'Senior Software Engineer',
                'Tech Lead',
                'Solutions Architect',
                'CTO',
                'Engineering Manager',
            ]),
        ], $overrides));
    }

    /**
     * Create a business-focused AuthorPersona
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return AuthorPersona
     */
    public static function business(array $overrides = []): AuthorPersona
    {
        $faker = self::faker();
        $expertise = $faker->randomElements(self::$expertiseByField['business'], $faker->numberBetween(2, 4));

        return self::make(array_merge([
            'author_expertise_areas' => $expertise,
            'author_job_title' => $faker->randomElement([
                'Business Consultant',
                'CEO',
                'Marketing Director',
                'Business Strategist',
                'Operations Manager',
            ]),
        ], $overrides));
    }

    /**
     * Create a design-focused AuthorPersona
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return AuthorPersona
     */
    public static function design(array $overrides = []): AuthorPersona
    {
        $faker = self::faker();
        $expertise = $faker->randomElements(self::$expertiseByField['design'], $faker->numberBetween(2, 4));

        return self::make(array_merge([
            'author_expertise_areas' => $expertise,
            'author_job_title' => $faker->randomElement([
                'Senior Designer',
                'Creative Director',
                'UX Designer',
                'Design Lead',
                'Product Designer',
            ]),
        ], $overrides));
    }

    /**
     * Create a content-focused AuthorPersona
     *
     * @param array<string, mixed> $overrides Values to override defaults
     * @return AuthorPersona
     */
    public static function content(array $overrides = []): AuthorPersona
    {
        $faker = self::faker();
        $expertise = $faker->randomElements(self::$expertiseByField['content'], $faker->numberBetween(2, 4));

        return self::make(array_merge([
            'author_expertise_areas' => $expertise,
            'author_job_title' => $faker->randomElement([
                'Content Strategist',
                'Senior Writer',
                'Content Marketing Manager',
                'Editorial Director',
                'SEO Specialist',
            ]),
        ], $overrides));
    }

    /**
     * Create an AuthorPersona with specific expertise areas
     *
     * @param array<int, string> $expertise Expertise areas
     * @param array<string, mixed> $overrides Additional overrides
     * @return AuthorPersona
     */
    public static function withExpertise(array $expertise, array $overrides = []): AuthorPersona
    {
        return self::make(array_merge(['author_expertise_areas' => $expertise], $overrides));
    }

    /**
     * Create an AuthorPersona with a specific name
     *
     * @param string $name Author name
     * @param array<string, mixed> $overrides Additional overrides
     * @return AuthorPersona
     */
    public static function withName(string $name, array $overrides = []): AuthorPersona
    {
        return self::make(array_merge(['author_name' => $name], $overrides));
    }

    /**
     * Create an AuthorPersona with a specific company
     *
     * @param string $company Company name
     * @param array<string, mixed> $overrides Additional overrides
     * @return AuthorPersona
     */
    public static function withCompany(string $company, array $overrides = []): AuthorPersona
    {
        return self::make(array_merge(['author_company' => $company], $overrides));
    }

    /**
     * Generate a realistic bio based on author details
     *
     * @param string $name Author name
     * @param string $jobTitle Job title
     * @param string $company Company name
     * @return string
     */
    private static function generateBio(string $name, string $jobTitle, string $company): string
    {
        $faker = self::faker();

        $templates = [
            "{$name} is a {$jobTitle} at {$company} with over {years} years of experience in the industry. Passionate about innovation and excellence, {firstName} has helped numerous organizations achieve their goals through strategic thinking and practical solutions.",
            "{$name} serves as {$jobTitle} at {$company}, bringing {years} years of expertise to the role. {firstName} is dedicated to delivering high-quality results and sharing knowledge with the community through writing and speaking engagements.",
            "As {$jobTitle} at {$company}, {$name} has spent {years} years mastering the craft and helping teams succeed. {firstName} believes in continuous learning and regularly contributes insights to help others grow in their careers.",
        ];

        $template = $faker->randomElement($templates);
        $years = $faker->numberBetween(5, 15);
        $firstName = explode(' ', $name)[0];

        $bio = str_replace(
            ['{$name}', '{$jobTitle}', '{$company}', '{years}', '{firstName}'],
            [$name, $jobTitle, $company, $years, $firstName],
            $template
        );

        // Ensure bio is under 500 characters
        if (strlen($bio) > 500) {
            $bio = substr($bio, 0, 497) . '...';
        }

        return $bio;
    }

    /**
     * Get a job title based on field
     *
     * @param string $field Field name
     * @return string
     */
    private static function getJobTitle(string $field): string
    {
        $faker = self::faker();

        $titles = [
            'technology' => [
                'Senior Software Engineer',
                'Tech Lead',
                'Solutions Architect',
                'Engineering Manager',
                'DevOps Engineer',
            ],
            'business' => [
                'Business Consultant',
                'Marketing Director',
                'Business Strategist',
                'Operations Manager',
                'Sales Director',
            ],
            'design' => [
                'Senior Designer',
                'Creative Director',
                'UX Designer',
                'Design Lead',
                'Product Designer',
            ],
            'content' => [
                'Content Strategist',
                'Senior Writer',
                'Content Marketing Manager',
                'Editorial Director',
                'SEO Specialist',
            ],
        ];

        return $faker->randomElement($titles[$field] ?? $titles['technology']);
    }
}
