<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        });
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
