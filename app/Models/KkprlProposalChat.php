<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KkprlProposalChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'chapter',
        'messages',
        'last_interaction_at',
    ];

    protected $casts = [
        'messages' => 'array',
        'last_interaction_at' => 'datetime',
    ];

    /**
     * Get the proposal that owns this chat.
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(KkprlProposal::class, 'proposal_id');
    }
}
