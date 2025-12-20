<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Regulation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'document_number',
        'description',
        'file_path',
        'download_count',
        'is_published',
        'extracted_text',
        'is_chunked',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $casts = [
        'is_published' => 'boolean',
        'is_chunked' => 'boolean',
        'download_count' => 'integer',
    ];

    /**
     * Get the chunks for this regulation.
     */
    public function chunks()
    {
        return $this->hasMany(\App\Models\RegulationChunk::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->isDirty('title')) {
                $slug = Str::slug($model->title);
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $model->id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                $model->slug = $slug;
            }
        });
    }
}
