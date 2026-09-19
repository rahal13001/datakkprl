<?php

namespace App\Services;

use App\Domain\Kkprl\ActiveRevisionExists;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalReviewEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class KkprlProposalRevisionService
{
    public function requestRevision(KkprlProposal $submitted, string $reason, User $actor): KkprlProposal
    {
        if (! $actor->can('Review:KkprlProposal')) {
            throw new AuthorizationException('Permission denied.');
        }

        if (blank($reason)) {
            throw new \InvalidArgumentException('Revision reason is required.');
        }

        $rootId = $submitted->root_proposal_id ?: $submitted->id;

        $copiedPaths = [];

        try {
            return DB::transaction(function () use ($submitted, $reason, $actor, $rootId, &$copiedPaths): KkprlProposal {
                KkprlProposal::query()->lockForUpdate()->findOrFail($rootId);

                if (KkprlProposal::query()->where('root_proposal_id', $rootId)->where('status', 'draft')->where('revision_number', '>', 0)->exists()) {
                    throw new ActiveRevisionExists;
                }

                if ($submitted->status !== 'submitted') {
                    throw new \InvalidArgumentException('Only submitted proposals can enter revision.');
                }

                $submitted->update(['status' => 'needs_revision']);
                $nextNumber = (int) KkprlProposal::query()->where('root_proposal_id', $rootId)->max('revision_number') + 1;
                $revision = KkprlProposal::create([
                    'root_proposal_id' => $rootId,
                    'revision_of_id' => $submitted->id,
                    'revision_number' => $nextNumber,
                    'revision_label' => 'rev-'.$nextNumber,
                    'ticket_number' => $submitted->ticket_number,
                    'status' => 'draft',
                    'current_step' => $submitted->current_step,
                    'progress_percent' => $submitted->progress_percent,
                    'applicant_type' => $submitted->applicant_type,
                    'applicant_name' => $submitted->applicant_name,
                    'institution_name' => $submitted->institution_name,
                    'phone' => $submitted->phone,
                    'email' => $submitted->email,
                    'province' => $submitted->province,
                    'regency' => $submitted->regency,
                    'activity_type' => $submitted->activity_type,
                    'payload' => $submitted->payload,
                    'form_version' => $submitted->form_version,
                    'template_version' => $submitted->template_version,
                    'last_saved_at' => now(),
                ]);

                foreach ($submitted->attachments as $attachment) {
                    if ($attachment->storage_disk !== config('kkprl.storage_disk', 'kkprl_private')) {
                        throw new \RuntimeException('Revision attachment storage is not private.');
                    }

                    $newPath = 'kkprl/proposals/'.$rootId.'/'.$revision->id.'/'.$attachment->chapter.'/'.basename($attachment->storage_path);
                    if (! Storage::disk($attachment->storage_disk)->copy($attachment->storage_path, $newPath)) {
                        throw new \RuntimeException('Revision attachment copy failed.');
                    }
                    $copiedPaths[] = [$attachment->storage_disk, $newPath];
                    $revision->attachments()->create($attachment->only([
                        'chapter', 'section', 'field', 'attachment_role', 'placement', 'anchor_key',
                        'display_order', 'caption', 'original_name', 'storage_disk', 'mime_type',
                        'size', 'checksum', 'uploaded_at',
                    ]) + ['storage_path' => $newPath]);
                }

                KkprlProposalReviewEvent::create([
                    'proposal_id' => $submitted->id,
                    'event_type' => 'needs_revision',
                    'actor_type' => 'staff',
                    'actor_id' => $actor->id,
                    'reason' => $reason,
                    'before_payload' => $submitted->payload,
                    'after_payload' => $submitted->payload,
                    'metadata' => [
                        'revision_id' => $revision->id,
                        'actor' => $this->actorMetadata($actor),
                    ],
                    'request_id' => request()->header('X-Request-ID'),
                    'created_at' => now(),
                ]);

                return $revision->fresh();
            });
        } catch (\Throwable $exception) {
            foreach ($copiedPaths as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    private function actorMetadata(User $actor): array
    {
        return [
            'id' => $actor->id,
            'name' => $actor->name,
            'roles' => $actor->getRoleNames()->values()->all(),
            'permissions' => $actor->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
