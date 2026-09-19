<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KkprlProposalReviewEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'proposal_id', 'event_type', 'actor_type', 'actor_id', 'reason',
        'before_payload', 'after_payload', 'metadata', 'request_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_payload' => 'encrypted:array',
            'after_payload' => 'encrypted:array',
            'metadata' => 'encrypted:array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \LogicException('Proposal review events are append-only.');
        });

        static::deleting(function (): void {
            throw new \LogicException('Proposal review events are append-only.');
        });
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(KkprlProposal::class, 'proposal_id');
    }
}
