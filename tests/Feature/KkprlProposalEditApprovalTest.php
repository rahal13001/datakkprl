<?php

namespace Tests\Feature;

use App\Domain\Kkprl\EditApprovalDenied;
use App\Domain\Kkprl\EditApprovalUsed;
use App\Domain\Kkprl\EditScopeDenied;
use App\Domain\Kkprl\EditSessionExpired;
use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalEditApproval;
use App\Models\KkprlProposalReviewEvent;
use App\Models\User;
use App\Services\KkprlProposalEditApprovalService;
use App\Services\KkprlProposalSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KkprlProposalEditApprovalTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function requester_cannot_self_approve_even_with_approval_permission(): void
    {
        [$requester, $approval] = $this->approvalFixture(true);

        $this->expectException(EditApprovalDenied::class);
        app(KkprlProposalEditApprovalService::class)->approve($approval, $requester, now()->addHour());
    }

    #[Test]
    public function locked_approval_row_still_rejects_self_approval_with_a_stale_model(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $approval->requested_by = User::factory()->create()->id;

        $this->expectException(EditApprovalDenied::class);
        app(KkprlProposalEditApprovalService::class)->approve($approval, $requester, now()->addHour());
    }

    #[Test]
    public function locked_approval_row_still_rejects_self_rejection_with_a_stale_model(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $approval->requested_by = User::factory()->create()->id;

        $this->expectException(EditApprovalDenied::class);
        app(KkprlProposalEditApprovalService::class)->reject($approval, $requester, 'Tidak disetujui.');
    }

    #[Test]
    public function second_officer_can_approve_and_requester_gets_one_scoped_session(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        $approver->givePermissionTo(Permission::findOrCreate('RunEditSession:KkprlProposal', 'web'));
        $requester->givePermissionTo(Permission::findOrCreate('RunEditSession:KkprlProposal', 'web'));
        $service = app(KkprlProposalEditApprovalService::class);

        $approved = $service->approve($approval, $approver, now()->addHour());
        $session = $service->startSession($approved, $requester);
        $changed = $service->applyChanges($session, $requester, ['bag-1.latitude' => '1.234'], 'Correct latitude.');

        $this->assertSame('1.234', $changed->payload['bag-1']['latitude']);
        $event = KkprlProposalReviewEvent::query()
            ->where('event_type', 'staff_edit')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame([], $event->before_payload);
        $this->assertSame('1.234', $event->after_payload['bag-1']['latitude']);
        $this->assertDatabaseHas('kkprl_proposal_review_events', ['event_type' => 'staff_edit', 'actor_id' => $requester->id]);
        $this->assertDatabaseHas('kkprl_proposal_review_events', ['event_type' => 'edit_approval_requested', 'actor_id' => $requester->id]);
        $this->assertDatabaseHas('kkprl_proposal_review_events', ['event_type' => 'edit_approved', 'actor_id' => $approver->id]);
        $this->assertDatabaseHas('kkprl_proposal_review_events', ['event_type' => 'edit_session_started', 'actor_id' => $requester->id]);
        $this->assertNotNull($session->approval->fresh()->used_at);
        $this->assertSame('used', $session->approval->fresh()->status);

        $this->expectException(EditApprovalUsed::class);
        $service->startSession($approved->fresh(), $requester);
    }

    #[Test]
    public function field_outside_approval_scope_and_expired_session_are_rejected(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        $requester->givePermissionTo(Permission::findOrCreate('RunEditSession:KkprlProposal', 'web'));
        $service = app(KkprlProposalEditApprovalService::class);
        $approved = $service->approve($approval, $approver, now()->addHour());
        $session = $service->startSession($approved, $requester);

        $this->expectException(EditScopeDenied::class);
        $service->applyChanges($session, $requester, ['bag-2.narrative' => 'not allowed'], 'Wrong scope.');
    }

    #[Test]
    public function approval_scope_rejects_unknown_proposal_paths(): void
    {
        [$requester, $proposal, $revision] = $this->approvalRequestFixture();

        $this->expectException(EditScopeDenied::class);
        app(KkprlProposalEditApprovalService::class)->request(
            $proposal,
            $revision,
            $requester,
            'Attempt to widen scope.',
            ['fields' => ['bag-1.internal_secret']],
        );
    }

    #[Test]
    public function one_active_edit_approval_is_allowed_per_revision(): void
    {
        [$requester, $proposal, $revision] = $this->approvalRequestFixture();
        $service = app(KkprlProposalEditApprovalService::class);

        $service->request(
            $proposal,
            $revision,
            $requester,
            'Correct location.',
            ['fields' => ['bag-1.latitude']],
        );

        $this->expectException(EditApprovalDenied::class);
        $service->request(
            $proposal,
            $revision,
            $requester,
            'Correct longitude.',
            ['fields' => ['bag-1.longitude']],
        );
    }

    #[Test]
    public function expired_approval_does_not_block_a_new_request(): void
    {
        [$requester, $proposal, $revision] = $this->approvalRequestFixture();
        $service = app(KkprlProposalEditApprovalService::class);
        $approval = $service->request(
            $proposal,
            $revision,
            $requester,
            'Correct latitude.',
            ['fields' => ['bag-1.latitude']],
        );
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        $approved = $service->approve($approval, $approver, now()->addMinute());
        $approved->update(['expires_at' => now()->subMinute()]);

        $replacement = $service->request(
            $proposal,
            $revision,
            $requester,
            'Correct longitude.',
            ['fields' => ['bag-1.longitude']],
        );

        $this->assertSame('expired', $approved->fresh()->status);
        $this->assertSame('pending', $replacement->status);
        $this->assertDatabaseHas('kkprl_proposal_review_events', [
            'event_type' => 'edit_approval_expired',
            'actor_type' => 'system',
        ]);
    }

    #[Test]
    public function approval_scope_rejects_nested_paths_beyond_a_known_field(): void
    {
        [$requester, $proposal, $revision] = $this->approvalRequestFixture();

        $this->expectException(EditScopeDenied::class);
        app(KkprlProposalEditApprovalService::class)->request(
            $proposal,
            $revision,
            $requester,
            'Attempt to widen a field path.',
            ['fields' => ['bag-1.latitude.internal']],
        );
    }

    #[Test]
    public function section_scope_rejects_unknown_change_paths(): void
    {
        [$requester, $proposal, $revision] = $this->approvalRequestFixture();
        $approval = app(KkprlProposalEditApprovalService::class)->request(
            $proposal,
            $revision,
            $requester,
            'Correct section data.',
            ['sections' => ['bag-1']],
        );
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        $requester->givePermissionTo(Permission::findOrCreate('RunEditSession:KkprlProposal', 'web'));
        $service = app(KkprlProposalEditApprovalService::class);
        $session = $service->startSession($service->approve($approval, $approver, now()->addHour()), $requester);

        $this->expectException(EditScopeDenied::class);
        $service->applyChanges($session, $requester, ['bag-1.internal_secret' => 'must not persist'], 'Reject unknown path.');
    }

    #[Test]
    public function expired_approval_is_not_startable(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        $service = app(KkprlProposalEditApprovalService::class);
        $approved = $service->approve($approval, $approver, now()->addMinutes(2));
        $approved->update(['expires_at' => now()->subMinute()]);
        $requester->givePermissionTo(Permission::findOrCreate('RunEditSession:KkprlProposal', 'web'));

        $this->expectException(EditSessionExpired::class);
        $service->startSession($approved->fresh(), $requester);
    }

    #[Test]
    public function sweeper_closes_active_sessions_after_expiry(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        $requester->givePermissionTo(Permission::findOrCreate('RunEditSession:KkprlProposal', 'web'));
        $service = app(KkprlProposalEditApprovalService::class);
        $session = $service->startSession($service->approve($approval, $approver, now()->addMinute()), $requester);

        $this->assertSame(1, $service->expireActiveSessions(now()->addMinutes(2)));
        $this->assertSame('expired', $session->fresh()->status);
        $this->assertDatabaseHas('kkprl_proposal_review_events', [
            'event_type' => 'edit_session_expired',
            'actor_type' => 'system',
        ]);
    }

    #[Test]
    public function sweeper_expires_unused_approved_approval(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        $service = app(KkprlProposalEditApprovalService::class);
        $approved = $service->approve($approval, $approver, now()->addMinute());

        $this->assertSame(1, $service->expireActiveSessions(now()->addMinutes(2)));
        $this->assertSame('expired', $approved->fresh()->status);
        $this->assertDatabaseHas('kkprl_proposal_review_events', [
            'event_type' => 'edit_approval_expired',
            'actor_type' => 'system',
            'proposal_id' => $approved->revision_id,
        ]);
    }

    #[Test]
    public function submitting_revision_ends_root_scoped_edit_session(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        $requester->givePermissionTo(Permission::findOrCreate('RunEditSession:KkprlProposal', 'web'));
        $service = app(KkprlProposalEditApprovalService::class);
        $session = $service->startSession($service->approve($approval, $approver, now()->addHour()), $requester);
        $revision = $approval->revision()->firstOrFail();
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        $revision->update(['payload' => $payload]);

        app(KkprlProposalSubmissionService::class)->submit($revision->fresh());

        $this->assertSame('ended', $session->fresh()->status);
        $this->assertSame('submitted', $revision->fresh()->status);
    }

    #[Test]
    public function stale_approval_cannot_be_approved_after_revision_submission(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $revision = $approval->revision()->firstOrFail();
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        $revision->update(['payload' => $payload]);
        app(KkprlProposalSubmissionService::class)->submit($revision->fresh());

        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));

        $this->expectException(EditApprovalDenied::class);
        app(KkprlProposalEditApprovalService::class)->approve($approval, $approver, now()->addHour());
    }

    #[Test]
    public function cancelling_session_records_the_staff_actor(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        $requester->givePermissionTo(Permission::findOrCreate('RunEditSession:KkprlProposal', 'web'));
        $service = app(KkprlProposalEditApprovalService::class);
        $session = $service->startSession($service->approve($approval, $approver, now()->addHour()), $requester);

        $service->endSession($session, 'cancelled', $requester);

        $this->assertDatabaseHas('kkprl_proposal_review_events', [
            'event_type' => 'edit_session_cancelled',
            'actor_id' => $requester->id,
        ]);
    }

    #[Test]
    public function another_officer_cannot_end_the_edit_session(): void
    {
        [$requester, $approval] = $this->approvalFixture(false);
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        $requester->givePermissionTo(Permission::findOrCreate('RunEditSession:KkprlProposal', 'web'));
        $other = User::factory()->create();
        $other->givePermissionTo(Permission::findOrCreate('RunEditSession:KkprlProposal', 'web'));
        $service = app(KkprlProposalEditApprovalService::class);
        $session = $service->startSession($service->approve($approval, $approver, now()->addHour()), $requester);

        $this->expectException(EditApprovalDenied::class);
        $service->endSession($session, 'cancelled', $other);
    }

    /** @return array{0: User, 1: KkprlProposalEditApproval} */
    private function approvalFixture(bool $giveApprovalToRequester): array
    {
        [$requester, $proposal, $revision] = $this->approvalRequestFixture($giveApprovalToRequester);

        $approval = app(KkprlProposalEditApprovalService::class)->request(
            $proposal,
            $revision,
            $requester,
            'Correct location.',
            ['fields' => ['bag-1.latitude']],
        );

        return [$requester, $approval];
    }

    /** @return array{0: User, 1: KkprlProposal, 2: KkprlProposal} */
    private function approvalRequestFixture(bool $giveApprovalToRequester = false): array
    {
        $requester = User::factory()->create();
        $requester->givePermissionTo(Permission::findOrCreate('RequestEdit:KkprlProposal', 'web'));
        if ($giveApprovalToRequester) {
            $requester->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));
        }
        $proposal = KkprlProposal::create(['phone' => '0812-3456-7890', 'root_proposal_id' => null]);
        $revision = KkprlProposal::create([
            'root_proposal_id' => $proposal->id,
            'revision_of_id' => $proposal->id,
            'revision_number' => 1,
            'revision_label' => 'rev-1',
            'ticket_number' => $proposal->ticket_number,
            'phone' => '0812-3456-7890',
            'status' => 'draft',
        ]);

        return [$requester, $proposal, $revision];
    }
}
