<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne as HasOneSurvey;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'access_token',
        // 'contact_details', // Deprecated, keeping for backup
        'name',
        'email',
        'whatsapp',
        'instance',
        'address',
        'booking_type',
        
        'status',          // pending, scheduled, waiting_approval, finished, canceled
        'metadata',
        'service_id',
        'consultation_location_id',
        'activity_type',
        'supporting_documents',
        'coordinate_file',
    ];

    public function getRouteKeyName(): string
    {
        return 'ticket_number';
    }

    protected $casts = [
        'contact_details' => 'array', // Keep casting just in case we need to read old data
        'metadata' => 'array',
        'access_token' => 'string', // It's a uuid string
        'supporting_documents' => 'array',
    ];

    /**
     * Boot logic for auto-generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // FIX: Populate legacy contact_details to prevent SQL error (1364)
            if (is_null($model->contact_details)) {
                $model->contact_details = [];
            }

            // Generate UUID Access Token
            if (empty($model->access_token)) {
                $model->access_token = (string) Str::uuid();
            }

            // Generate Ticket Number: TICKET-YYYYMMDD-Random(4)
            if (empty($model->ticket_number)) {
                $date = Carbon::now('Asia/Jayapura')->format('Ymd');
                $random = strtoupper(Str::random(4));
                $model->ticket_number = "TICKET-{$date}-{$random}";
                
                // Ensure uniqueness mainly for the random part collision (rare but possible)
                while (static::where('ticket_number', $model->ticket_number)->exists()) {
                    $random = strtoupper(Str::random(4));
                    $model->ticket_number = "TICKET-{$date}-{$random}";
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function consultationReports(): HasMany
    {
        return $this->hasMany(ConsultationReport::class);
    }

    public function latestConsultationReport(): HasOne
    {
        return $this->hasOne(ConsultationReport::class)->latestOfMany();
    }

    public function assignments(): HasManyThrough
    {
        return $this->hasManyThrough(Assignment::class, Schedule::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function consultationLocation(): BelongsTo
    {
        return $this->belongsTo(ConsultationLocation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Helpers
    |--------------------------------------------------------------------------
    */

    // Legacy getters removed to allow direct column access
}
