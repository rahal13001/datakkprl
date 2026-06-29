<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningMaterialAccess extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'visit_count' => 'integer',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(LearningSession::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LearningActivityLog::class);
    }
}
