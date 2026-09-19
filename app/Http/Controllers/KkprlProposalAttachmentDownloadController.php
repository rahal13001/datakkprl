<?php

namespace App\Http\Controllers;

use App\Models\KkprlProposalAttachment;
use App\Services\KkprlProposalAccessService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class KkprlProposalAttachmentDownloadController
{
    public function __invoke(
        KkprlProposalAttachment $attachment,
        KkprlProposalAccessService $access,
    ): Response {
        $current = $access->current();
        $staffAllowed = auth()->check() && auth()->user()->can('Download:KkprlProposalAttachment');

        if (! $staffAllowed && ($current === null || $attachment->proposal_id !== $current->id)) {
            abort(404);
        }

        abort_unless($attachment->storage_disk === config('kkprl.storage_disk', 'kkprl_private'), 404);
        abort_unless(Storage::disk($attachment->storage_disk)->exists($attachment->storage_path), 404);

        return Storage::disk($attachment->storage_disk)->download(
            $attachment->storage_path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type],
        );
    }
}
