<?php

namespace ErdiKoroglu\AIContentGenerator\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ContentMetadata Model
 * 
 * Stores metadata about generated content including SEO information,
 * keyword analysis, and generation details.
 * 
 * @property int $id
 * @property string $content_id
 * @property string $focus_keyword
 * @property array|null $related_keywords
 * @property string|null $search_intent
 * @property string|null $content_type
 * @property string|null $locale
 * @property int|null $word_count
 * @property float|null $keyword_density
 * @property string|null $ai_provider
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ContentMetadata extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'content_metadata';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'content_id',
        'focus_keyword',
        'related_keywords',
        'search_intent',
        'content_type',
        'locale',
        'word_count',
        'keyword_density',
        'ai_provider',
        'generated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'related_keywords' => 'array',
        'word_count' => 'integer',
        'keyword_density' => 'decimal:2',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
