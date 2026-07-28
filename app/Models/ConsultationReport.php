<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ConsultationReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'content',
        'status',
        'reviewed_by',
        'reviewed_at',
        'feedback',
        'documentation',
        'officer_signature',
        'signed_by',
        'signed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'documentation' => 'array',
        'content_encrypted' => 'encrypted',
        'feedback_encrypted' => 'encrypted',
        'documentation_encrypted' => 'encrypted:array',
        'officer_signature_encrypted' => 'encrypted',
        'signed_at' => 'datetime',
    ];

    protected function content(): Attribute
    {
        return $this->protectedAttribute('content', 'content_encrypted');
    }

    protected function feedback(): Attribute
    {
        return $this->protectedAttribute('feedback', 'feedback_encrypted');
    }

    protected function documentation(): Attribute
    {
        return $this->protectedAttribute('documentation', 'documentation_encrypted', true);
    }

    protected function officerSignature(): Attribute
    {
        return Attribute::make(
            get: fn () => filled($this->attributes['officer_signature_encrypted'] ?? null)
                ? $this->getAttributeValue('officer_signature_encrypted')
                : null,
            set: fn ($value) => [
                'officer_signature_encrypted' => $value === null
                    ? null
                    : static::currentEncrypter()->encrypt($value, false),
            ],
        );
    }

    protected function protectedAttribute(string $legacyColumn, string $encryptedColumn, bool $legacyIsJson = false)
    {
        return Attribute::make(
            get: function ($value) use ($encryptedColumn, $legacyIsJson) {
                if (filled($this->attributes[$encryptedColumn] ?? null)) {
                    return $this->getAttributeValue($encryptedColumn);
                }

                if ($legacyIsJson && is_string($value)) {
                    $decoded = json_decode($value, true);

                    return is_array($decoded) ? $decoded : $value;
                }

                return $value;
            },
            set: fn ($value) => [
                $legacyColumn => null,
                $encryptedColumn => $this->encryptProtectedValue($value, $legacyIsJson),
            ],
        );
    }

    protected function encryptProtectedValue(mixed $value, bool $legacyIsJson = false): ?string
    {
        if ($value === null) {
            return null;
        }

        return static::currentEncrypter()->encrypt(
            $legacyIsJson && is_array($value) ? json_encode($value) : $value,
            false,
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
