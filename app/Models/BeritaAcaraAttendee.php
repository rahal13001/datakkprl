<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaAcaraAttendee extends Model
{
    use HasFactory;

    protected $table = 'berita_acara_attendees';

    protected $fillable = [
        'berita_acara_id',
        'nama',
        'jabatan',
        'instansi',
        'tanda_tangan',
        'is_officer',
    ];

    protected $casts = [
        'is_officer' => 'boolean',
    ];

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
}
