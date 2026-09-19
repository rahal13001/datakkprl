<?php

namespace App\Services;

use App\Domain\Kkprl\EditApprovalDenied;
use App\Domain\Kkprl\EditApprovalUsed;
use App\Domain\Kkprl\EditScopeDenied;
use App\Domain\Kkprl\EditSessionExpired;
use App\Domain\Kkprl\ProposalChapterRules;
use App\Domain\Kkprl\ProposalFieldCatalog;
use App\Domain\Kkprl\ProposalPayloadSanitizer;
use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalEditApproval;
use App\Models\KkprlProposalEditSession;
use App\Models\KkprlProposalReviewEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class KkprlProposalEditApprovalService
{
    public function __construct(
        private readonly ProposalPayloadSanitizer $sanitizer,
    ) {}

    /** @param array<string, mixed> $scope */
    public function request(
        KkprlProposal $proposal,
        KkprlProposal $revision,
        User $requester,
        string $reason,
        array $scope,
    ): KkprlProposalEditApproval {
        $this->assertPermission($requester, 'RequestEdit:KkprlProposal');
        $this->assertRevisionScope($proposal, $revision);

        if (blank($reason) || ! $this->hasScope($scope)) {
            throw new EditApprovalDenied('Reason and scope are required.');
        }

        return DB::transaction(function () use ($proposal, $revision, $requester, $reason, $scope): KkprlProposalEditApproval {
            $lockedProposal = KkprlProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            $lockedRevision = KkprlProposal::query()->lockForUpdate()->findOrFail($revision->id);
            $this->assertRevisionScope($lockedProposal, $lockedRevision);

            $now = now();
            $expiredApprovals = KkprlProposalEditApproval::query()
                ->where('revision_id', $lockedRevision->id)
                ->where('status', 'approved')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', $now)
                ->lockForUpdate()
                ->get();
            foreach ($expiredApprovals as $expiredApproval) {
                $expiredApproval->update(['status' => 'expired']);
                $this->lifecycleEvent(
                    $expiredApproval->fresh(),
                    'edit_approval_expired',
                    null,
                    'Edit approval expired before a new request.',
                );
            }

            if (KkprlProposalEditApproval::query()
                ->where('revision_id', $lockedRevision->id)
                ->where(function ($query) use ($now): void {
                    $query->where('status', 'pending')
                        ->orWhere(function ($query) use ($now): void {
                            $query->where('status', 'approved')
                                ->where(function ($query) use ($now): void {
                                    $query->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', $now);
                                });
                        });
                })
                ->exists()) {
                throw new EditApprovalDenied('An active edit approval already exists for this revision.');
            }
            if (KkprlProposalEditSession::query()
                ->where('revision_id', $lockedRevision->id)
                ->where('status', 'active')
                ->exists()) {
                throw new EditApprovalDenied('An active edit session already exists for this revision.');
            }
            $normalizedScope = $this->normalizeScope($scope, $lockedRevision->payload ?? []);
            $approval = KkprlProposalEditApproval::create([
                'proposal_id' => $lockedProposal->id,
                'revision_id' => $lockedRevision->id,
                'requested_by' => $requester->id,
                'status' => 'pending',
                'reason' => $reason,
                'scope' => $normalizedScope,
                'actor_metadata' => $this->actorMetadata($requester),
                'requested_at' => now(),
            ]);
            $event = $this->lifecycleEvent($approval, 'edit_approval_requested', $requester, $reason);
            $approval->update(['approval_event_id' => $event->id]);

            return $approval->fresh();
        });
    }

    public function approve(
        KkprlProposalEditApproval $approval,
        User $approver,
        CarbonInterface $expiresAt,
    ): KkprlProposalEditApproval {
        $this->assertPermission($approver, 'ApproveEdit:KkprlProposal');

        if ((int) $approval->requested_by === (int) $approver->id) {
            throw new EditApprovalDenied('Requester cannot approve own edit request.');
        }

        return DB::transaction(function () use ($approval, $approver, $expiresAt): KkprlProposalEditApproval {
            $locked = KkprlProposalEditApproval::query()->lockForUpdate()->findOrFail($approval->id);
            if ((int) $locked->requested_by === (int) $approver->id) {
                throw new EditApprovalDenied('Requester cannot approve own edit request.');
            }

            $this->assertRevisionScope(
                $locked->proposal()->firstOrFail(),
                $locked->revision()->firstOrFail(),
            );

            if ($locked->status !== 'pending' || $expiresAt->isPast()) {
                throw new EditApprovalDenied('Approval is not pending or expiry is invalid.');
            }

            $locked->update([
                'approved_by' => $approver->id,
                'status' => 'approved',
                'approved_at' => now(),
                'expires_at' => $expiresAt,
                'actor_metadata' => array_merge($locked->actor_metadata ?? [], [
                    'approved_by' => $this->actorMetadata($approver),
                ]),
            ]);
            $approvalEvent = $this->lifecycleEvent($locked->fresh(), 'edit_approved', $approver, 'Edit approval approved.');
            $locked->update(['approval_event_id' => $approvalEvent->id]);

            return $locked->fresh();
        });
    }

    public function startSession(KkprlProposalEditApproval $approval, User $actor): KkprlProposalEditSession
    {
        $this->assertPermission($actor, 'RunEditSession:KkprlProposal');

        if ((int) $approval->requested_by !== (int) $actor->id) {
            throw new EditApprovalDenied('Only the requesting officer can run the edit session.');
        }

        return DB::transaction(function () use ($approval, $actor): KkprlProposalEditSession {
            $locked = KkprlProposalEditApproval::query()->lockForUpdate()->findOrFail($approval->id);

            if ($locked->used_at !== null || $locked->status !== 'approved') {
                throw new EditApprovalUsed;
            }

            if ($locked->expires_at === null || $locked->expires_at->isPast()) {
                $locked->update(['status' => 'expired']);
                throw new EditSessionExpired;
            }

            $this->assertRevisionScope(
                $locked->proposal()->firstOrFail(),
                $locked->revision()->firstOrFail(),
            );
            if (KkprlProposalEditSession::query()
                ->where('revision_id', $locked->revision_id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->exists()) {
                throw new EditApprovalDenied('An active edit session already exists for this revision.');
            }

            $session = KkprlProposalEditSession::create([
                'proposal_id' => $locked->proposal_id,
                'revision_id' => $locked->revision_id,
                'approval_id' => $locked->id,
                'started_by' => $actor->id,
                'status' => 'active',
                'session_started_at' => now(),
                'expires_at' => $locked->expires_at,
            ]);
            $locked->update(['status' => 'used', 'used_at' => now(), 'edit_session_id' => $session->id]);
            $this->lifecycleEvent($locked->fresh(), 'edit_session_started', $actor, 'Edit session started.', [
                'edit_session_id' => $session->id,
            ]);

            return $session;
        });
    }

    public function expireActiveSessions(?CarbonInterface $now = null): int
    {
        $cutoff = $now ?? now();
        $expired = 0;

        $approvalIds = KkprlProposalEditApproval::query()
            ->where('status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $cutoff)
            ->pluck('id');

        foreach ($approvalIds as $approvalId) {
            $didExpire = DB::transaction(function () use ($approvalId, $cutoff): bool {
                $approval = KkprlProposalEditApproval::query()->lockForUpdate()->find($approvalId);

                if ($approval === null
                    || $approval->status !== 'approved'
                    || $approval->expires_at === null
                    || $approval->expires_at->isAfter($cutoff)) {
                    return false;
                }

                $approval->update(['status' => 'expired']);
                $this->lifecycleEvent(
                    $approval->fresh(),
                    'edit_approval_expired',
                    null,
                    'Edit approval expired before a session started.',
                );

                return true;
            });

            if ($didExpire) {
                $expired++;
            }
        }

        $sessionIds = KkprlProposalEditSession::query()
            ->where('status', 'active')
            ->where('expires_at', '<=', $cutoff)
            ->pluck('id');

        foreach ($sessionIds as $sessionId) {
            $didExpire = DB::transaction(function () use ($sessionId, $cutoff): bool {
                $session = KkprlProposalEditSession::query()->lockForUpdate()->find($sessionId);

                if ($session === null || $session->status !== 'active' || $session->expires_at->isAfter($cutoff)) {
                    return false;
                }

                $this->endSession($session, 'expired');

                return true;
            });

            if ($didExpire) {
                $expired++;
            }
        }

        return $expired;
    }

    public function reject(KkprlProposalEditApproval $approval, User $actor, string $reason): KkprlProposalEditApproval
    {
        $this->assertPermission($actor, 'ApproveEdit:KkprlProposal');

        if ((int) $approval->requested_by === (int) $actor->id) {
            throw new EditApprovalDenied('Requester cannot reject own edit request.');
        }

        if (blank($reason)) {
            throw new EditApprovalDenied('Rejection reason is required.');
        }

        return DB::transaction(function () use ($approval, $actor, $reason): KkprlProposalEditApproval {
            $locked = KkprlProposalEditApproval::query()->lockForUpdate()->findOrFail($approval->id);
            if ((int) $locked->requested_by === (int) $actor->id) {
                throw new EditApprovalDenied('Requester cannot reject own edit request.');
            }

            if ($locked->status !== 'pending') {
                throw new EditApprovalDenied('Approval is not pending.');
            }

            $locked->update([
                'approved_by' => $actor->id,
                'status' => 'rejected',
                'approved_at' => now(),
                'reason' => $locked->reason."\nRejection: ".$reason,
                'actor_metadata' => array_merge($locked->actor_metadata ?? [], [
                    'rejected_by' => $this->actorMetadata($actor),
                ]),
            ]);
            $approvalEvent = $this->lifecycleEvent($locked->fresh(), 'edit_rejected', $actor, $reason);
            $locked->update(['approval_event_id' => $approvalEvent->id]);

            return $locked->fresh();
        });
    }

    public function applyChanges(
        KkprlProposalEditSession $session,
        User $actor,
        array $changes,
        string $reason,
    ): KkprlProposal {
        $this->assertPermission($actor, 'RunEditSession:KkprlProposal');

        if ($changes === [] || blank($reason)) {
            throw new EditApprovalDenied('Changes and reason are required.');
        }

        return DB::transaction(function () use ($session, $actor, $changes, $reason): KkprlProposal {
            $lockedSession = KkprlProposalEditSession::query()->lockForUpdate()->findOrFail($session->id);
            $this->assertActiveSession($lockedSession, $actor);

            $revision = KkprlProposal::query()->lockForUpdate()->findOrFail($lockedSession->revision_id);
            $scope = $lockedSession->approval()->first()?->scope ?? [];
            $scope = $this->normalizeScope($scope, $revision->payload ?? []);
            $before = $revision->payload ?? [];
            $after = $before;

            foreach ($changes as $field => $value) {
                if (! $this->isKnownScopePath((string) $field, $before, false)) {
                    throw new EditScopeDenied((string) $field);
                }

                if (! $this->scopeAllows($scope, (string) $field)) {
                    throw new EditScopeDenied((string) $field);
                }
                data_set($after, $field, $value);
            }

            $after = $this->sanitizer->sanitize($after);

            $progress = app(ProposalProgress::class)->evaluate($after);
            $revision->update([
                'payload' => $after,
                'progress_percent' => $progress->percent,
                'last_saved_at' => now(),
            ]);

            KkprlProposalReviewEvent::create([
                'proposal_id' => $revision->id,
                'event_type' => 'staff_edit',
                'actor_type' => 'staff',
                'actor_id' => $actor->id,
                'reason' => $reason,
                'before_payload' => $before,
                'after_payload' => $after,
                'metadata' => [
                    'edit_session_id' => $lockedSession->id,
                    'approval_id' => $lockedSession->approval_id,
                    'actor' => $this->actorMetadata($actor),
                ],
                'request_id' => request()->header('X-Request-ID'),
                'created_at' => now(),
            ]);

            return $revision->fresh();
        });
    }

    public function endSession(
        KkprlProposalEditSession $session,
        string $reason = 'cancelled',
        ?User $actor = null,
    ): void {
        if (! in_array($reason, ['cancelled', 'expired', 'submitted'], true)) {
            throw new \InvalidArgumentException('Invalid edit session end reason.');
        }

        if ($session->status !== 'active') {
            return;
        }

        if ($actor !== null) {
            $this->assertPermission($actor, 'RunEditSession:KkprlProposal');
            if ((int) $session->started_by !== (int) $actor->id) {
                throw new EditApprovalDenied('Only the requesting officer can end the edit session.');
            }
        }

        $session->update([
            'status' => $reason === 'expired' ? 'expired' : 'ended',
            'session_finished_at' => now(),
            'ended_at' => now(),
            'end_reason' => $reason,
        ]);
        $this->lifecycleEvent($session->approval, 'edit_session_'.$reason, $actor, $reason, [
            'edit_session_id' => $session->id,
        ]);
    }

    private function assertActiveSession(KkprlProposalEditSession $session, User $actor): void
    {
        if ((int) $session->started_by !== (int) $actor->id || $session->status !== 'active') {
            throw new EditSessionExpired;
        }

        if ($session->expires_at->isPast()) {
            $this->endSession($session, 'expired', $actor);
            throw new EditSessionExpired;
        }
    }

    private function assertRevisionScope(KkprlProposal $proposal, KkprlProposal $revision): void
    {
        if ($revision->revision_number < 1 || $revision->root_proposal_id !== ($proposal->root_proposal_id ?: $proposal->id) || $revision->status !== 'draft') {
            throw new EditApprovalDenied('Approval scope does not match revision.');
        }
    }

    private function assertPermission(User $user, string $permission): void
    {
        if (! $user->can($permission)) {
            throw new EditApprovalDenied('Permission denied.');
        }
    }

    /** @param array<string, mixed> $scope */
    private function scopeAllows(array $scope, string $field): bool
    {
        if (in_array($field, $scope['fields'] ?? [], true)) {
            return true;
        }

        foreach ($scope['sections'] ?? [] as $section) {
            if ($field === $section || str_starts_with($field, rtrim($section, '.').'.')) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $scope @param array<string, mixed> $payload @return array<string, list<string>> */
    private function normalizeScope(array $scope, array $payload): array
    {
        $normalized = ['fields' => [], 'sections' => []];

        foreach (['fields', 'sections'] as $type) {
            $paths = $scope[$type] ?? [];
            if ($paths !== [] && ! is_array($paths)) {
                throw new EditScopeDenied((string) $paths);
            }

            foreach ($paths as $path) {
                $path = trim((string) $path);
                if ($path === '' || ! $this->isKnownScopePath($path, $payload, $type === 'sections')) {
                    throw new EditScopeDenied($path);
                }
                $normalized[$type][] = $path;
            }
            $normalized[$type] = array_values(array_unique($normalized[$type]));
        }

        if ($normalized['fields'] === [] && $normalized['sections'] === []) {
            throw new EditApprovalDenied('Reason and scope are required.');
        }

        return $normalized;
    }

    /** @param array<string, mixed> $payload */
    private function isKnownScopePath(string $path, array $payload, bool $section): bool
    {
        if (! preg_match('/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/', $path)) {
            return false;
        }

        $segments = explode('.', $path);
        $conditions = ['includes_reclamation', 'land_relation', 'has_existing_permits'];
        if (count($segments) === 1 && in_array($path, $conditions, true)) {
            return ! $section;
        }

        $chapter = $segments[0];
        $relevant = app(ProposalChapterRules::class)->relevantChapters($payload);
        if (! in_array($chapter, $relevant, true)) {
            return false;
        }

        if (count($segments) === 1) {
            return $section;
        }

        if ($section || count($segments) !== 2) {
            return false;
        }

        $knownFields = ProposalFieldCatalog::fieldsForPayload($chapter, $payload);
        if (! in_array($segments[1], $knownFields, true)) {
            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $scope */
    private function hasScope(array $scope): bool
    {
        return collect($scope['fields'] ?? [])->contains(fn (mixed $field): bool => filled($field))
            || collect($scope['sections'] ?? [])->contains(fn (mixed $section): bool => filled($section));
    }

    /** @return array<string, mixed> */
    private function actorMetadata(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }

    /** @param array<string, mixed> $metadata */
    private function lifecycleEvent(
        KkprlProposalEditApproval $approval,
        string $eventType,
        ?User $actor,
        string $reason,
        array $metadata = [],
    ): KkprlProposalReviewEvent {
        return KkprlProposalReviewEvent::create([
            'proposal_id' => $approval->revision_id,
            'event_type' => $eventType,
            'actor_type' => $actor === null ? 'system' : 'staff',
            'actor_id' => $actor?->id,
            'reason' => $reason,
            'metadata' => array_merge($metadata, [
                'approval_id' => $approval->id,
                'revision_id' => $approval->revision_id,
                'actor' => $actor === null ? null : $this->actorMetadata($actor),
            ]),
            'request_id' => request()->header('X-Request-ID'),
            'created_at' => now(),
        ]);
    }
}
