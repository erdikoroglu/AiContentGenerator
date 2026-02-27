<?php

namespace ErdiKoroglu\AIContentGenerator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \ErdiKoroglu\AIContentGenerator\DTOs\ContentResponse generate(\ErdiKoroglu\AIContentGenerator\DTOs\ContentRequest $request)
 * @method static self setAIProvider(string $provider)
 * @method static self setImageProvider(string $provider)
 *
 * @see \ErdiKoroglu\AIContentGenerator\Services\ContentGeneratorService
 */
class AIContentGenerator extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'ai-content-generator';
    }
}
