<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KkprlProposalDocument extends Model
{
    protected $fillable = [
        'proposal_id',
        'chapter',
        'format',
        'generation_scope',
        'template_version',
        'snapshot_hash',
        'attachment_manifest_hash',
        'snapshot_payload_encrypted',
        'attachment_manifest_encrypted',
        'storage_disk',
        'storage_path',
        'generation_status',
        'error_code',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_payload_encrypted' => 'encrypted:array',
            'attachment_manifest_encrypted' => 'encrypted:array',
            'generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $document): void {
            $document->storage_disk ??= config('kkprl.storage_disk', 'kkprl_private');
            if ($document->storage_disk !== config('kkprl.storage_disk', 'kkprl_private')) {
                throw new \LogicException('KKPRL documents must use the private storage disk.');
            }
        });

        static::updating(function (self $document): void {
            $immutable = [
                'proposal_id', 'chapter', 'format', 'generation_scope', 'template_version',
                'snapshot_hash', 'attachment_manifest_hash', 'snapshot_payload_encrypted',
                'attachment_manifest_encrypted',
            ];

            if (array_intersect(array_keys($document->getDirty()), $immutable) !== []) {
                throw new \LogicException('Proposal document snapshots are immutable.');
            }

            if ($document->getOriginal('generation_status') === 'generated'
                && $document->isDirty('storage_path')) {
                throw new \LogicException('Generated proposal document paths are immutable.');
            }
        });

        static::deleting(function (): void {
            throw new \LogicException('Proposal document snapshots are append-only.');
        });
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(KkprlProposal::class, 'proposal_id');
    }
}
