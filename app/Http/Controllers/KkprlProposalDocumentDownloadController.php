<?php

namespace App\Http\Controllers;

use App\Models\KkprlProposalDocument;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalSnapshotService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class KkprlProposalDocumentDownloadController
{
    public function __invoke(
        KkprlProposalDocument $document,
        KkprlProposalAccessService $access,
        KkprlProposalSnapshotService $snapshots,
    ): Response {
        $staffAllowed = auth('web')->check() && auth('web')->user()->can('Download:KkprlProposalDocument');

        if (! $staffAllowed && ($document->proposal === null || ! $access->canReadDocuments($document->proposal))) {
            abort(404);
        }

        abort_unless($document->generation_status === 'generated' && filled($document->storage_path), 404);
        abort_unless($document->storage_disk === config('kkprl.storage_disk', 'kkprl_private'), 404);
        abort_unless(Storage::disk($document->storage_disk)->exists($document->storage_path), 404);

        $proposal = $document->proposal()->first();
        abort_if($proposal === null, 404);

        try {
            $snapshot = $snapshots->capture($proposal, $document->chapter);
        } catch (ValidationException) {
            abort(404);
        }

        abort_unless(
            hash_equals((string) $document->snapshot_hash, $snapshot->snapshotHash)
                && hash_equals((string) $document->attachment_manifest_hash, $snapshot->attachmentManifestHash),
            404,
        );

        abort_unless($staffAllowed || $access->canReadDocuments($proposal), 404);

        return Storage::disk($document->storage_disk)->download(
            $document->storage_path,
            'proposal-'.$document->chapter.'.'.$document->format,
        );
    }
}
