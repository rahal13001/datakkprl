<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SatisfactionSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'criticism',
        'suggestion',
    ];

    protected $casts = [
        'criticism_encrypted' => 'encrypted',
        'suggestion_encrypted' => 'encrypted',
    ];

    protected function criticism(): Attribute
    {
        return $this->protectedAttribute('criticism', 'criticism_encrypted');
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
