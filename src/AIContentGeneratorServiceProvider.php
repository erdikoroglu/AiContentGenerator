<?php

namespace ErdiKoroglu\AIContentGenerator;

use ErdiKoroglu\AIContentGenerator\Console\Commands\GenerateContentCommand;
use Illuminate\Support\ServiceProvider;

class AIContentGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration with application configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/ai-content-generator.php',
            'ai-content-generator'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/ai-content-generator.php' => config_path('ai-content-generator.php'),
        ], 'ai-content-generator-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'ai-content-generator-migrations');

        // Load migrations if running in console
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
            
            // Register Artisan commands
            $this->commands([
                GenerateContentCommand::class,
            ]);
        }
    }
}
