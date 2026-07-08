<?php

namespace App\Models;

use App\Services\DataHashService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'access_token',
        'contact_details', // Deprecated compatibility column; keep empty for new writes.
        'name',
        'email',
        'whatsapp',
        'instance',
        'address',
        'booking_type',

        'status',          // waiting, scheduled, completed
        'agreed_to_terms', // boolean agreement
        'metadata',
        'service_id',
        'consultation_location_id',
        'activity_type',
        'supporting_documents',
        'supporting_document_links',
        'coordinate_file',
        'access_token_hash',
        'email_hash',
        'whatsapp_hash',
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
        'supporting_document_links' => 'array',
        'access_token_encrypted' => 'encrypted',
        'name_encrypted' => 'encrypted',
        'email_encrypted' => 'encrypted',
        'whatsapp_encrypted' => 'encrypted',
        'instance_encrypted' => 'encrypted',
        'address_encrypted' => 'encrypted',
        'metadata_encrypted' => 'encrypted:array',
        'supporting_documents_encrypted' => 'encrypted:array',
        'supporting_document_links_encrypted' => 'encrypted:array',
        'coordinate_file_encrypted' => 'encrypted',
    ];

    /**
     * Boot logic for auto-generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Read old payloads if present, but never persist new PII in this legacy column.
            if (is_null($model->contact_details)) {
                $model->contact_details = [];
            }

            $contactDetails = is_array($model->contact_details) ? $model->contact_details : [];
            $model->name ??= $contactDetails['name'] ?? null;
            $model->email ??= $contactDetails['email'] ?? null;
            $model->whatsapp ??= $contactDetails['wa'] ?? $contactDetails['whatsapp'] ?? null;
            $model->instance ??= $contactDetails['agency'] ?? $contactDetails['instance'] ?? null;
            $model->contact_details = [];
            $model->status ??= 'waiting';

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

            static::populateProtectionHashes($model);
        });

        static::saving(function (self $model) {
            static::populateProtectionHashes($model);
        });
    }

    protected static function populateProtectionHashes(self $model): void
    {
        $hash = app(DataHashService::class);
        $model->access_token_hash = $hash->token($model->access_token);
        $model->email_hash = $hash->email($model->email);
        $model->whatsapp_hash = $hash->phone($model->whatsapp);
    }

    protected function accessToken(): Attribute
    {
        return $this->protectedAttribute('access_token', 'access_token_encrypted');
    }

    protected function name(): Attribute
    {
        return $this->protectedAttribute('name', 'name_encrypted');
    }

    protected function email(): Attribute
    {
        return $this->protectedAttribute('email', 'email_encrypted');
    }

    protected function whatsapp(): Attribute
    {
        return $this->protectedAttribute('whatsapp', 'whatsapp_encrypted');
    }

    protected function instance(): Attribute
    {
        return $this->protectedAttribute('instance', 'instance_encrypted');
    }

    protected function address(): Attribute
    {
        return $this->protectedAttribute('address', 'address_encrypted');
    }

    protected function metadata(): Attribute
    {
        return $this->protectedAttribute('metadata', 'metadata_encrypted', true);
    }

    protected function supportingDocuments(): Attribute
    {
        return $this->protectedAttribute('supporting_documents', 'supporting_documents_encrypted', true);
    }

    protected function supportingDocumentLinks(): Attribute
    {
        return $this->protectedAttribute('supporting_document_links', 'supporting_document_links_encrypted', true);
    }

    protected function coordinateFile(): Attribute
    {
        return $this->protectedAttribute('coordinate_file', 'coordinate_file_encrypted');
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

    public function matchesAccessToken(?string $token): bool
    {
        if (blank($token)) {
            return false;
        }

        $hash = app(DataHashService::class)->token($token);

        return ($hash !== null && hash_equals((string) $this->access_token_hash, $hash))
            || ($this->access_token_hash === null && hash_equals((string) $this->access_token, (string) $token));
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

    public function beritaAcara(): HasOne
    {
        return $this->hasOne(BeritaAcara::class);
    }

    public function satisfactionSurvey(): HasOne
    {
        return $this->hasOne(SatisfactionSurvey::class);
    }

    public function hasSatisfactionFeedback(): bool
    {
        return $this->assignments()->whereNotNull('score')->exists()
            || $this->satisfactionSurvey()->exists();
    }

    public function requiresCostSavingsEstimate(): bool
    {
        return (bool) $this->consultationLocation?->requires_cost_savings_estimate;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Helpers
    |--------------------------------------------------------------------------
    */

    // Legacy getters removed to allow direct column access
}
