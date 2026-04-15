<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeritaAcara extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'berita_acara';

    protected $fillable = [
        'client_id',
        'nomor_berita_acara',
        'kbli',
        'tanggal_pelaksanaan',
        'lokasi_permohonan',
        'hasil_pendampingan',
        'lampiran_peta',
        'lampiran_dokumentasi',
        'lampiran_lainnya',
        'tanda_tangan_pemohon',
        'status',
    ];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'lampiran_dokumentasi' => 'array',
        'lampiran_lainnya' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(BeritaAcaraAttendee::class);
    }

    public function officers(): HasMany
    {
        return $this->hasMany(BeritaAcaraAttendee::class)->where('is_officer', true);
    }

    public function externalAttendees(): HasMany
    {
        return $this->hasMany(BeritaAcaraAttendee::class)->where('is_officer', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get the activity type label for the Berita Acara template.
     * Maps to "Persetujuan" (business) or "Konfirmasi" (non_business).
     */
    public function getJenisPermohonanLabel(): string
    {
        return match ($this->client?->activity_type) {
            'business' => 'Persetujuan',
            'non_business' => 'Konfirmasi',
            default => 'Persetujuan/Konfirmasi',
        };
    }
}
