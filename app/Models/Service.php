<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'is_active',
        'requires_documents',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $casts = [
        'is_active' => 'boolean',
        'requires_documents' => 'boolean',
    ];

    public function clients(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Client::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $slug = Str::slug($model->name);
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $model->id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                $model->slug = $slug;
            } elseif ($model->isDirty('name') && !empty($model->slug)) {
                 // Should we auto-update slug on name change? 
                 // Usually for SEO preservation we don't, unless explicit. 
                 // But requirements say "automatically generate logic... ensure uniqueness".
                 // Let's stick to generating if empty, or updating if explicitly requested. 
                 // User Requirement: "automatically generate ... from name ... whenever a record is Created or Updated"
                 // This implies enforcing sync.
                $slug = Str::slug($model->name);
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
