<?php

namespace ErdiKoroglu\AIContentGenerator\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AuthorPersona Model
 * 
 * Represents an author persona with expertise areas and biographical information
 * for E-E-A-T compliance in content generation.
 * 
 * @property int $id
 * @property string $author_name
 * @property string|null $author_company
 * @property string|null $author_job_title
 * @property array $author_expertise_areas
 * @property string|null $author_short_bio
 * @property string|null $author_url
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AuthorPersona extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'author_personas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'author_name',
        'author_company',
        'author_job_title',
        'author_expertise_areas',
        'author_short_bio',
        'author_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'author_expertise_areas' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
