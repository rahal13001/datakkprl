<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function access(): BelongsTo
    {
        return $this->belongsTo(LearningMaterialAccess::class, 'learning_material_access_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LearningActivityLog::class);
    }
}
