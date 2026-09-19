<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KkprlProposalEditSession extends Model
{
    protected $fillable = [
        'proposal_id', 'revision_id', 'approval_id', 'started_by', 'status',
        'session_started_at', 'session_finished_at', 'expires_at', 'ended_at', 'end_reason',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'session_started_at' => 'datetime',
            'session_finished_at' => 'datetime',
            'ended_at' => 'datetime',
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

    public function approval(): BelongsTo
    {
        return $this->belongsTo(KkprlProposalEditApproval::class, 'approval_id');
    }
}
