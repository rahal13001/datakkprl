<?php

namespace App\Services;

use App\Domain\Kkprl\ProposalLocked;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalDocument;
use App\Models\KkprlProposalReviewEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class KkprlProposalDocumentDeletionService
{
    public function __construct(private readonly KkprlProposalAccessService $access) {}

    public function delete(int $documentId): void
    {
        $account = $this->access->assertApproved();
        $document = KkprlProposalDocument::query()
            ->whereHas('proposal', fn ($query) => $query->where('applicant_id', $account->id))
            ->findOrFail($documentId);

        DB::transaction(function () use ($document): void {
            // Match generation's parent/account lock order, then recheck ownership.
            $proposal = KkprlProposal::query()->lockForUpdate()->findOrFail($document->proposal_id);
            $this->access->assertCanBuild($proposal, lock: true);
            if ($proposal->isLocked()) {
                throw new ProposalLocked;
            }
            $document = $proposal->documents()->lockForUpdate()->findOrFail($document->id);
            if ($document->deleted_at !== null) {
                return;
            }
            if ($document->generation_status !== 'generated') {
                throw ValidationException::withMessages(['documents' => 'Hanya dokumen hasil generate yang dapat dihapus.']);
            }
            $this->assertSafePath($proposal, $document);

            $document->forceFill(['deleted_at' => now(), 'deleted_by_applicant_id' => $proposal->applicant_id])->save();
            KkprlProposalReviewEvent::create([
                'proposal_id' => $proposal->id,
                'event_type' => 'document_deleted',
                'actor_type' => 'applicant',
                'actor_id' => $proposal->applicant_id,
                'metadata' => $document->only(['chapter', 'format', 'snapshot_hash', 'attachment_manifest_hash'])
                    + ['document_id' => $document->id],
                'created_at' => now(),
            ]);

            $disk = Storage::disk($document->storage_disk);
            // Unlink only this artifact. A failed unlink rolls back metadata/audit.
            try {
                $removed = ! $disk->exists($document->storage_path)
                    || ($disk->delete($document->storage_path) && ! $disk->exists($document->storage_path));
            } catch (Throwable) {
                $removed = false;
            }
            if (! $removed) {
                throw ValidationException::withMessages(['documents' => 'Dokumen belum berhasil dihapus. Coba lagi.']);
            }
        });
    }

    private function assertSafePath(KkprlProposal $proposal, KkprlProposalDocument $document): void
    {
        $diskName = (string) config('kkprl.storage_disk', 'kkprl_private');
        $prefix = "kkprl/proposals/{$proposal->root_proposal_id}/{$proposal->id}/documents/";
        $filename = "{$document->chapter}-{$document->snapshot_hash}";
        $allowed = [
            $prefix.$filename."-{$document->attachment_manifest_hash}.{$document->format}",
            $prefix.$filename.".{$document->format}",
        ];
        $valid = $document->storage_disk === $diskName
            && config("filesystems.disks.{$diskName}.driver") === 'local'
            && in_array($document->chapter, ['bag-1', 'bag-2', 'bag-3', 'bag-4', 'bag-5'], true)
            && in_array($document->format, ['docx', 'pdf'], true)
            && preg_match('/^[a-f0-9]{64}$/D', $document->snapshot_hash)
            && preg_match('/^[a-f0-9]{64}$/D', $document->attachment_manifest_hash)
            && in_array($document->storage_path, $allowed, true);
        if (! $valid) {
            throw ValidationException::withMessages(['documents' => 'Lokasi dokumen tidak aman untuk dihapus. Hubungi petugas.']);
        }
        if (KkprlProposalDocument::where('storage_disk', $diskName)->where('storage_path', $document->storage_path)
            ->where('id', '!=', $document->id)->exists()) {
            throw ValidationException::withMessages(['documents' => 'Berkas direferensikan dokumen lain. Hubungi petugas.']);
        }

        $disk = Storage::disk($diskName);
        $root = realpath($disk->path(''));
        $target = $root === false ? '' : rtrim(str_replace('\\', '/', $root), '/').'/'.$document->storage_path;
        // Reject symlink aliases even when they resolve somewhere on the private disk.
        $cursor = $root;
        foreach (explode('/', $document->storage_path) as $segment) {
            $cursor .= DIRECTORY_SEPARATOR.$segment;
            if (is_link($cursor)) {
                throw ValidationException::withMessages(['documents' => 'Lokasi dokumen tidak aman untuk dihapus. Hubungi petugas.']);
            }
        }
        $resolved = realpath($disk->path($document->storage_path));
        if ($root === false || ($resolved !== false
            && (str_replace('\\', '/', $resolved) !== $target || ! is_file($resolved)))) {
            throw ValidationException::withMessages(['documents' => 'Lokasi dokumen tidak aman untuk dihapus. Hubungi petugas.']);
        }
    }
}
