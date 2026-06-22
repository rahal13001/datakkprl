<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SatisfactionSurveyResult extends Model
{
    use HasFactory;

    public const QUARTERS = [
        1 => 'Triwulan I',
        2 => 'Triwulan II',
        3 => 'Triwulan III',
        4 => 'Triwulan IV',
    ];

    protected $fillable = [
        'year',
        'quarter',
        'title',
        'description',
        'image_path',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public static function quarterOptions(): array
    {
        return self::QUARTERS;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getQuarterLabelAttribute(): string
    {
        return self::QUARTERS[$this->quarter] ?? 'Triwulan '.$this->quarter;
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: "Hasil SKM {$this->quarter_label} {$this->year}";
    }

    public function getImageUrlAttribute(): string
    {
        if (Str::startsWith($this->image_path, ['http://', 'https://', '/'])) {
            return $this->image_path;
        }

        return '/storage/'.ltrim($this->image_path, '/');
    }
}
