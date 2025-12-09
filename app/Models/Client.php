<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'access_token',
        'contact_details', // {name, email, wa, agency}
        'status',          // pending, scheduled, waiting_approval, finished, canceled
        'metadata',
        'service_id',
        'activity_type',
    ];

    public function getRouteKeyName(): string
    {
        return 'ticket_number';
    }

    protected $casts = [
        'contact_details' => 'array',
        'metadata' => 'array',
        'access_token' => 'string', // It's a uuid string
    ];

    /**
     * Boot logic for auto-generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
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

    public function assignments(): HasManyThrough
    {
        return $this->hasManyThrough(Assignment::class, Schedule::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Helper to get email from JSON contact_details
     */
    public function getEmailAttribute(): ?string
    {
        return $this->contact_details['email'] ?? null;
    }
}
