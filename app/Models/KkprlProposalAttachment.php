<?php

namespace App\Models;

use App\Domain\Kkprl\InvalidAttachmentFile;
use App\Domain\Kkprl\ProposalLocked;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KkprlProposalAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'proposal_id',
        'chapter',
        'section',
        'field',
        'attachment_role',
        'placement',
        'anchor_key',
        'display_order',
        'caption',
        'original_name',
        'storage_disk',
        'storage_path',
        'mime_type',
        'size',
        'checksum',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $attachment): void {
            $attachment->storage_disk ??= config('kkprl.storage_disk', 'kkprl_private');
            if ($attachment->storage_disk !== config('kkprl.storage_disk', 'kkprl_private')) {
                throw new InvalidAttachmentFile('KKPRL attachments must use the private storage disk.');
            }

            $proposal = KkprlProposal::query()->find($attachment->proposal_id);
            if ($proposal?->isLocked()) {
                throw new ProposalLocked;
            }
        });

        static::deleting(function (self $attachment): void {
            $proposal = KkprlProposal::query()->find($attachment->proposal_id);
            if ($proposal?->isLocked()) {
                throw new ProposalLocked;
            }
        });
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(KkprlProposal::class, 'proposal_id');
    }
}
