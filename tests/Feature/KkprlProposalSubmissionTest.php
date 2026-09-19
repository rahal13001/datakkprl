<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalLocked;
use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposalAttachment;
use App\Models\KkprlProposalReviewEvent;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDraftService;
use App\Services\KkprlProposalSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KkprlProposalSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kkprl_private');
    }

    #[Test]
    public function incomplete_draft_cannot_be_submitted(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);

        $this->expectException(ValidationException::class);
        app(KkprlProposalSubmissionService::class)->submit($proposal);
    }

    #[Test]
    public function complete_draft_becomes_locked_submitted(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];

        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }

        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $submitted = app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());

        $this->assertSame('submitted', $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertNotNull($submitted->generated_at);
        $this->assertTrue($submitted->isLocked());
        $this->assertDatabaseCount('kkprl_proposal_documents', 6);
        $this->assertDatabaseMissing('kkprl_proposal_documents', [
            'generation_status' => 'failed',
        ]);
        $this->assertDatabaseHas('kkprl_proposal_review_events', [
            'proposal_id' => $submitted->id,
            'event_type' => 'submitted',
        ]);
    }

    #[Test]
    public function document_generation_failure_keeps_proposal_as_draft(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];

        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }

        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $attachment = app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->image('photo.webp', 20, 40),
        );
        Storage::disk('kkprl_private')->put($attachment->storage_path, 'corrupted WebP render source');

        $this->expectException(\RuntimeException::class);
        try {
            app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());
        } finally {
            $this->assertSame('draft', $proposal->fresh()->status);
            $this->assertDatabaseMissing('kkprl_proposal_review_events', ['event_type' => 'submitted']);
        }
    }

    #[Test]
    public function invalid_manual_coordinates_block_submission(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '999' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);

        $this->expectException(ValidationException::class);
        app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());
    }

    #[Test]
    public function needs_revision_original_remains_locked(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $proposal->update(['status' => 'needs_revision']);

        $this->expectException(ProposalLocked::class);
        app(KkprlProposalDraftService::class)->save($proposal, [], 1);
    }

    #[Test]
    public function submitted_model_rejects_direct_payload_mutation(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $submitted = app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());
        $submitted->payload = $payload + ['tampered' => 'x'];

        $this->expectException(ProposalLocked::class);
        $submitted->save();
    }

    #[Test]
    public function submitted_proposal_cannot_be_reopened_as_a_draft(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $submitted = app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());
        $submitted->status = 'draft';

        $this->expectException(ProposalLocked::class);
        $submitted->save();
    }

    #[Test]
    public function submitted_proposal_rejects_direct_attachment_mutation(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $attachment = KkprlProposalAttachment::create([
            'proposal_id' => $proposal->id,
            'chapter' => 'bag-1',
            'attachment_role' => 'supporting',
            'placement' => 'appendix',
            'original_name' => 'support.png',
            'storage_disk' => 'kkprl_private',
            'storage_path' => 'kkprl/test/support.png',
            'mime_type' => 'image/png',
            'size' => 10,
        ]);
        $proposal->update(['status' => 'submitted']);

        $this->expectException(ProposalLocked::class);
        $attachment->update(['caption' => 'Tampered']);
    }

    #[Test]
    public function review_events_are_append_only(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $event = KkprlProposalReviewEvent::create([
            'proposal_id' => $proposal->id,
            'event_type' => 'test',
            'actor_type' => 'system',
            'reason' => 'Immutable audit fixture.',
            'created_at' => now(),
        ]);

        $this->expectException(\LogicException::class);
        $event->update(['reason' => 'Changed']);
    }

    #[Test]
    public function review_events_cannot_be_deleted(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $event = KkprlProposalReviewEvent::create([
            'proposal_id' => $proposal->id,
            'event_type' => 'test',
            'actor_type' => 'system',
            'created_at' => now(),
        ]);

        $this->expectException(\LogicException::class);
        $event->delete();
    }
}
