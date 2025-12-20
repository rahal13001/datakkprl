<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegulationChunk extends Model
{
    protected $fillable = [
        'regulation_id',
        'chunk_index',
        'chunk_text',
        'word_count',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'word_count' => 'integer',
    ];

    /**
     * Get the parent regulation.
     */
    public function regulation(): BelongsTo
    {
        return $this->belongsTo(Regulation::class);
    }

    /**
     * Scope for FULLTEXT search.
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query
            ->whereRaw("MATCH(chunk_text) AGAINST(? IN NATURAL LANGUAGE MODE)", [$searchTerm])
            ->orderByRaw("MATCH(chunk_text) AGAINST(? IN NATURAL LANGUAGE MODE) DESC", [$searchTerm]);
    }

    /**
     * Scope for active/published regulations only.
     */
    public function scopeFromPublishedRegulations($query)
    {
        return $query->whereHas('regulation', function ($q) {
            $q->where('is_published', true)
              ->where('is_chunked', true)
              ->whereNull('deleted_at');
        });
    }
}
