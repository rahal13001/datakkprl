<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class BeritaAcaraAttendee extends Model
{
    use HasFactory;

    protected $table = 'berita_acara_attendees';

    protected $fillable = [
        'berita_acara_id',
        'token',
        'nama',
        'jabatan',
        'instansi',
        'email',
        'no_hp',
        'tanda_tangan',
        'is_officer',
        'is_signatory',
        'confirmed_at',
    ];

    protected $casts = [
        'is_officer' => 'boolean',
        'is_signatory' => 'boolean',
        'confirmed_at' => 'datetime',
        'token_encrypted' => 'encrypted',
        'nama_encrypted' => 'encrypted',
        'jabatan_encrypted' => 'encrypted',
        'instansi_encrypted' => 'encrypted',
        'email_encrypted' => 'encrypted',
        'no_hp_encrypted' => 'encrypted',
        'tanda_tangan_encrypted' => 'encrypted',
    ];

    /**
     * Boot logic for auto-generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = (string) Str::uuid();
            }

            static::populateProtectionHashes($model);
        });

        static::saving(function (self $model) {
            static::populateProtectionHashes($model);
        });
    }

    protected static function populateProtectionHashes(self $model): void
    {
        $hash = app(\App\Services\DataHashService::class);
        $model->token_hash = $hash->token($model->token);
        $model->email_hash = $hash->email($model->email);
    }

    protected function token(): Attribute
    {
        return $this->protectedAttribute('token', 'token_encrypted');
    }

    protected function nama(): Attribute
    {
        return $this->protectedAttribute('nama', 'nama_encrypted');
    }

    protected function jabatan(): Attribute
    {
        return $this->protectedAttribute('jabatan', 'jabatan_encrypted');
    }

    protected function instansi(): Attribute
    {
        return $this->protectedAttribute('instansi', 'instansi_encrypted');
    }

    protected function email(): Attribute
    {
        return $this->protectedAttribute('email', 'email_encrypted');
    }

    protected function noHp(): Attribute
    {
        return $this->protectedAttribute('no_hp', 'no_hp_encrypted');
    }

    protected function tandaTangan(): Attribute
    {
        return $this->protectedAttribute('tanda_tangan', 'tanda_tangan_encrypted');
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

    /**
     * Use token for public URL resolution.
     */
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function beritaAcara(): BelongsTo
    {
        return $this->belongsTo(BeritaAcara::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get the jabatan/instansi combined label for the signature table.
     */
    public function getJabatanInstansiLabel(): string
    {
        $parts = array_filter([$this->jabatan, $this->instansi]);
        return implode(' / ', $parts) ?: '-';
    }

    /**
     * Check if this attendee has already confirmed/signed.
     */
    public function hasConfirmed(): bool
    {
        return !is_null($this->confirmed_at);
    }

    /**
     * Get the signing URL for this attendee.
     */
    public function getSigningUrl(): string
    {
        return route('berita-acara.sign', ['token' => $this->token]);
    }
}
