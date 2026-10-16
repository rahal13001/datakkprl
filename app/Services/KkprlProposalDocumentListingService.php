<?php

namespace App\Services;

use App\Models\KkprlProposal;
use App\Models\KkprlProposalDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class KkprlProposalDocumentListingService
{
    public function __construct(private readonly KkprlProposalSnapshotService $snapshots) {}

    /** Safe presentation metadata; callers must scope the query to owned proposals. */
    public function present(KkprlProposalDocument $document, KkprlProposal $proposal, bool $approved): array
    {
        $available = $document->deleted_at === null && $document->generation_status === 'generated'
            && $document->storage_disk === config('kkprl.storage_disk', 'kkprl_private')
            && filled($document->storage_path) && Storage::disk($document->storage_disk)->exists($document->storage_path);
        $current = false;
        if ($available) {
            try {
                $snapshot = $this->snapshots->captureForDocument($proposal, $document);
                $current = hash_equals((string) $document->snapshot_hash, $snapshot->snapshotHash)
                    && hash_equals((string) $document->attachment_manifest_hash, $snapshot->attachmentManifestHash);
            } catch (ValidationException) {
                // Stale/conditional/legacy files may be removed, never downloaded.
            }
        }

        return $document->only(['id', 'chapter', 'format', 'generated_at']) + [
            'can_download' => $current,
            'can_delete' => $approved && ! $proposal->isLocked() && $document->deleted_at === null,
            'status' => $current ? 'Siap diunduh' : ($available ? 'Versi lama' : 'Berkas tidak tersedia'),
            'proposal_reference' => $approved ? trim($proposal->ticket_number.' '.$proposal->revision_label) : null,
        ];
    }
}
