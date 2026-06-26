<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PublicFeedback extends Model
{
    use HasFactory;

    protected $table = 'public_feedback';

    protected $fillable = [
        'is_anonymous',
        'submitter_name',
        'feedback',
        'suggestion',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'submitter_name_encrypted' => 'encrypted',
        'feedback_encrypted' => 'encrypted',
        'suggestion_encrypted' => 'encrypted',
    ];

    protected function submitterName(): Attribute
    {
        return $this->protectedAttribute('submitter_name', 'submitter_name_encrypted');
    }

    protected function feedback(): Attribute
    {
        return $this->protectedAttribute('feedback', 'feedback_encrypted');
    }

    protected function suggestion(): Attribute
    {
        return $this->protectedAttribute('suggestion', 'suggestion_encrypted');
    }

    protected function protectedAttribute(string $legacyColumn, string $encryptedColumn)
    {
        return Attribute::make(
            get: fn ($value) => filled($this->attributes[$encryptedColumn] ?? null)
                ? $this->getAttributeValue($encryptedColumn)
                : $value,
            set: fn ($value) => [
                $legacyColumn => null,
                $encryptedColumn => $value === null
                    ? null
                    : static::currentEncrypter()->encrypt($value, false),
            ],
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'public_feedback_user')
            ->withTimestamps();
    }

    public function getPublicSubmitterLabelAttribute(): string
    {
        return $this->is_anonymous ? 'Anonim' : 'Pengguna Layanan';
    }

    public function getSubmitterDisplayAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonim';
        }

        return $this->submitter_name ?: 'Non-anonim';
    }
}
