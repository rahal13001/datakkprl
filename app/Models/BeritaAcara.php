<?php

namespace App\Models;

use App\Services\BusinessDayService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

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
        'signing_deadline',
        'attendance_url_token',
        'attendance_is_open',
    ];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'lampiran_dokumentasi' => 'array',
        'lampiran_lainnya' => 'array',
        'signing_deadline' => 'datetime',
        'attendance_is_open' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->attendance_url_token)) {
                $model->attendance_url_token = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

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

    public function signatories(): HasMany
    {
        return $this->hasMany(BeritaAcaraAttendee::class)->where('is_signatory', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get the full URL for the public attendance page.
     */
    public function getAttendanceUrl(): string
    {
        if (!$this->attendance_url_token) {
            $this->update(['attendance_url_token' => (string) \Illuminate\Support\Str::uuid()]);
        }
        return url('/berita-acara/attendance/' . $this->attendance_url_token);
    }

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

    /**
     * Calculate and set the signing deadline (3 business days from tanggal_pelaksanaan).
     */
    public function calculateSigningDeadline(): Carbon
    {
        $service = app(BusinessDayService::class);
        return $service->addBusinessDays($this->tanggal_pelaksanaan, 3);
    }

    /**
     * Check if the signing deadline has passed.
     */
    public function isSigningExpired(): bool
    {
        if (!$this->signing_deadline) {
            // Fallback: calculate from tanggal_pelaksanaan
            $deadline = $this->calculateSigningDeadline();
            return Carbon::now()->greaterThan($deadline->endOfDay());
        }

        return Carbon::now()->greaterThan($this->signing_deadline);
    }

    /**
     * Check if the BA is auto-approved (deadline passed and still draft).
     * When this returns true, all attendees are considered to have agreed.
     */
    public function isAutoApproved(): bool
    {
        return $this->isSigningExpired() && $this->status === 'draft';
    }
}
