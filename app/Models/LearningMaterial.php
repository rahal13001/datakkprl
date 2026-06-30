<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LearningMaterial extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_PDF = 'pdf';

    public const TYPE_VIDEO = 'video';

    protected $fillable = [
        'learning_group_id',
        'title',
        'slug',
        'description',
        'type',
        'pdf_path',
        'video_url',
        'thumbnail_path',
        'sort_order',
        'is_featured',
        'is_published',
        'view_count',
        'download_count',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'view_count' => 'integer',
        'download_count' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(LearningGroup::class, 'learning_group_id');
    }

    public function accessKey(): string
    {
        return 'learning-material:'.$this->getKey();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopePdf(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PDF);
    }

    public function scopeVideo(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_VIDEO);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->published()
            ->whereHas('group', fn (Builder $query) => $query->publiclyVisible());
    }

    public function isPdf(): bool
    {
        return $this->type === self::TYPE_PDF;
    }

    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    public function videoEmbedUrl(): ?string
    {
        if (! $this->isVideo() || blank($this->video_url)) {
            return null;
        }

        $parts = parse_url($this->video_url);
        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');

        if (str_contains($host, 'youtube.com')) {
            parse_str($parts['query'] ?? '', $query);
            $videoId = Arr::get($query, 'v');

            if ($videoId) {
                return 'https://www.youtube.com/embed/'.$videoId;
            }

            if (str_starts_with($path, 'embed/')) {
                return 'https://www.youtube.com/'.$path;
            }
        }

        if (str_contains($host, 'youtu.be') && filled($path)) {
            return 'https://www.youtube.com/embed/'.$path;
        }

        if (str_contains($host, 'vimeo.com') && filled($path)) {
            $videoId = collect(explode('/', $path))->filter()->last();

            if ($videoId) {
                return 'https://player.vimeo.com/video/'.$videoId;
            }
        }

        return null;
    }

    public function safeVideoUrl(): ?string
    {
        if (! $this->isVideo() || blank($this->video_url)) {
            return null;
        }

        return filter_var($this->video_url, FILTER_VALIDATE_URL) ? $this->video_url : null;
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
                    $slug = $originalSlug.'-'.$count++;
                }

                $model->slug = $slug;
            }

            if ($model->type === self::TYPE_PDF) {
                $model->video_url = null;
            }

            if ($model->type === self::TYPE_VIDEO) {
                $model->pdf_path = null;
            }
        });
    }
}
