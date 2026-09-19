<?php

namespace App\Models;

use App\Domain\Kkprl\KkprlPhoneNormalizer;
use App\Domain\Kkprl\ProposalLocked;
use App\Services\DataHashService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KkprlProposal extends Model
{
    protected $hidden = [
        'phone_encrypted',
        'email_encrypted',
        'applicant_name_encrypted',
        'institution_name_encrypted',
        'payload_encrypted',
        'phone_hash',
        'email_hash',
    ];

    protected $fillable = [
        'root_proposal_id',
        'revision_of_id',
        'revision_number',
        'ticket_number',
        'revision_label',
        'status',
        'current_step',
        'progress_percent',
        'applicant_type',
        'applicant_name',
        'institution_name',
        'phone',
        'email',
        'province',
        'regency',
        'activity_type',
        'payload',
        'form_version',
        'template_version',
        'last_saved_at',
        'generated_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'applicant_name_encrypted' => 'encrypted',
            'institution_name_encrypted' => 'encrypted',
            'phone_encrypted' => 'encrypted',
            'email_encrypted' => 'encrypted',
            'payload_encrypted' => 'encrypted:array',
            'last_saved_at' => 'datetime',
            'generated_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $proposal): void {
            $proposal->ticket_number ??= static::newTicketNumber();
            $proposal->status ??= 'draft';
        });

        static::created(function (self $proposal): void {
            if ($proposal->root_proposal_id === null) {
                $proposal->updateQuietly(['root_proposal_id' => $proposal->id]);
            }
        });

        static::saving(function (self $proposal): void {
            $normalizedPhone = app(KkprlPhoneNormalizer::class)->normalize($proposal->phone);
            $hashes = app(DataHashService::class);
            if ($proposal->phone_hash !== $hashes->phone($normalizedPhone)) {
                $proposal->phone = $normalizedPhone;
                $proposal->phone_hash = $hashes->phone($normalizedPhone);
            }
            if ($proposal->email_hash !== $hashes->email($proposal->email)) {
                $proposal->email_hash = $hashes->email($proposal->email);
            }
        });

        static::updating(function (self $proposal): void {
            $originalStatus = $proposal->getOriginal('status');
            if (! in_array($originalStatus, ['submitted', 'needs_revision'], true)) {
                return;
            }

            $dirty = array_diff(array_keys($proposal->getDirty()), ['status', 'updated_at']);
            if ($dirty !== []) {
                throw new ProposalLocked;
            }

            if (array_key_exists('status', $proposal->getDirty())
                && ($originalStatus !== 'submitted' || $proposal->status !== 'needs_revision')) {
                throw new ProposalLocked;
            }
        });

        static::deleting(function (self $proposal): void {
            $fresh = static::query()->find($proposal->id);
            if ($fresh?->isLocked() || $fresh?->reviewEvents()->exists()) {
                throw new ProposalLocked;
            }
        });
    }

    public function rootProposal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'root_proposal_id');
    }

    public function revisionOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revision_of_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'revision_of_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KkprlProposalAttachment::class, 'proposal_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KkprlProposalDocument::class, 'proposal_id');
    }

    public function reviewEvents(): HasMany
    {
        return $this->hasMany(KkprlProposalReviewEvent::class, 'proposal_id');
    }

    public function editApprovals(): HasMany
    {
        return $this->hasMany(KkprlProposalEditApproval::class, 'proposal_id');
    }

    public function editSessions(): HasMany
    {
        return $this->hasMany(KkprlProposalEditSession::class, 'proposal_id');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['submitted', 'needs_revision'], true);
    }

    protected static function newTicketNumber(): string
    {
        do {
            $ticket = 'KKPRL-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (static::query()->where('ticket_number', $ticket)->exists());

        return $ticket;
    }

    protected function applicantName(): Attribute
    {
        [$get, $set] = $this->protectedValueCallbacks('applicant_name', 'applicant_name_encrypted');

        return Attribute::make($get, $set);
    }

    protected function institutionName(): Attribute
    {
        [$get, $set] = $this->protectedValueCallbacks('institution_name', 'institution_name_encrypted');

        return Attribute::make($get, $set);
    }

    protected function phone(): Attribute
    {
        [$get, $set] = $this->protectedValueCallbacks('phone', 'phone_encrypted');

        return Attribute::make($get, $set);
    }

    protected function email(): Attribute
    {
        [$get, $set] = $this->protectedValueCallbacks('email', 'email_encrypted');

        return Attribute::make($get, $set);
    }

    protected function payload(): Attribute
    {
        [$get, $set] = $this->protectedValueCallbacks('payload', 'payload_encrypted', true);

        return Attribute::make($get, $set);
    }

    /** @return array{0: callable, 1: callable} */
    private function protectedValueCallbacks(string $legacyColumn, string $encryptedColumn, bool $json = false): array
    {
        return [
            function ($value) use ($encryptedColumn, $json) {
                if (filled($this->attributes[$encryptedColumn] ?? null)) {
                    return $this->getAttributeValue($encryptedColumn);
                }

                if ($json && is_string($value)) {
                    return json_decode($value, true) ?: [];
                }

                return $value;
            },
            fn ($value): array => [
                $legacyColumn => null,
                $encryptedColumn => $value === null
                    ? null
                    : static::currentEncrypter()->encrypt(
                        $json && is_array($value) ? json_encode($value) : $value,
                        false,
                    ),
            ],
        ];
    }
}
