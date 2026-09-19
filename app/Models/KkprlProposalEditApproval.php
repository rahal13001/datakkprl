<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KkprlProposalEditApproval extends Model
{
    protected $fillable = [
        'proposal_id', 'revision_id', 'requested_by', 'approved_by', 'status',
        'reason', 'scope', 'actor_metadata', 'requested_at', 'approved_at',
        'expires_at', 'used_at', 'approval_event_id', 'edit_session_id',
    ];

    protected function casts(): array
    {
        return [
            'scope' => 'encrypted:array',
            'actor_metadata' => 'encrypted:array',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(KkprlProposal::class, 'proposal_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(KkprlProposal::class, 'revision_id');
    }
}
