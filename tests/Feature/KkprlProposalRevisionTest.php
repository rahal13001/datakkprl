<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ActiveRevisionExists;
use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposalAttachment;
use App\Models\User;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDraftService;
use App\Services\KkprlProposalRevisionService;
use App\Services\KkprlProposalSubmissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KkprlProposalRevisionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function needs_revision_creates_immutable_copy_with_same_main_ticket(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $submitted = app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());
        $actor = $this->requester();

        $revision = app(KkprlProposalRevisionService::class)->requestRevision($submitted, 'Perbaiki uraian lokasi.', $actor);

        $this->assertSame('needs_revision', $submitted->fresh()->status);
        $this->assertSame('draft', $revision->status);
        $this->assertSame('rev-1', $revision->revision_label);
        $this->assertSame($submitted->ticket_number, $revision->ticket_number);
        $this->assertSame($submitted->root_proposal_id, $revision->root_proposal_id);
        $this->assertSame($submitted->payload, $revision->payload);
        $this->assertDatabaseHas('kkprl_proposal_review_events', [
            'proposal_id' => $submitted->id,
            'event_type' => 'needs_revision',
        ]);
        $event = $submitted->reviewEvents()->where('event_type', 'needs_revision')->latest('id')->firstOrFail();
        $this->assertSame($actor->id, $event->metadata['actor']['id']);
        $this->assertContains('Review:KkprlProposal', $event->metadata['actor']['permissions']);
    }

    #[Test]
    public function only_one_active_draft_revision_is_allowed(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $submitted = app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());
        $revisions = app(KkprlProposalRevisionService::class);
        $actor = $this->requester();
        $revisions->requestRevision($submitted, 'Perbaiki data.', $actor);

        $this->expectException(ActiveRevisionExists::class);
        $revisions->requestRevision($submitted->fresh(), 'Perbaiki lagi.', $actor);
    }

    #[Test]
    public function ordinary_revision_request_uses_review_permission_not_emergency_edit_permission(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $submitted = app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());

        $reviewer = User::factory()->create();
        $reviewer->givePermissionTo(Permission::findOrCreate('Review:KkprlProposal', 'web'));

        $revision = app(KkprlProposalRevisionService::class)->requestRevision($submitted, 'Perbaiki narasi.', $reviewer);

        $this->assertSame('rev-1', $revision->revision_label);
        $this->assertSame($reviewer->id, $submitted->reviewEvents()->latest('id')->firstOrFail()->actor_id);
    }

    #[Test]
    public function emergency_edit_permission_without_review_permission_cannot_request_ordinary_revision(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $submitted = app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());

        $emergencyEditor = User::factory()->create();
        $emergencyEditor->givePermissionTo(Permission::findOrCreate('RequestEdit:KkprlProposal', 'web'));

        $this->expectException(AuthorizationException::class);
        app(KkprlProposalRevisionService::class)->requestRevision($submitted, 'Perbaiki narasi.', $emergencyEditor);
    }

    #[Test]
    public function revision_copies_private_attachments_without_mutating_original(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $originalAttachment = app(KkprlProposalAttachmentService::class)->store(
            $proposal->fresh(), 'bag-1', UploadedFile::fake()->image('original.png'),
        );
        $submitted = app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());

        $revision = app(KkprlProposalRevisionService::class)->requestRevision($submitted, 'Perbaiki lampiran.', $this->requester());
        $copied = $revision->attachments()->first();

        $this->assertNotSame($originalAttachment->storage_path, $copied->storage_path);
        Storage::disk('kkprl_private')->assertExists($originalAttachment->storage_path);
        Storage::disk('kkprl_private')->assertExists($copied->storage_path);
        $this->assertSame($originalAttachment->checksum, $copied->checksum);
    }

    #[Test]
    public function failed_revision_copy_cleans_up_copied_attachments(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $originalAttachment = app(KkprlProposalAttachmentService::class)->store(
            $proposal->fresh(), 'bag-1', UploadedFile::fake()->image('original.png'),
        );
        $submitted = app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());
        $filesBeforeRevision = Storage::disk('kkprl_private')->allFiles();
        $eventName = 'eloquent.creating: '.KkprlProposalAttachment::class;
        $forceFailure = true;

        Event::listen($eventName, function (KkprlProposalAttachment $attachment) use (&$forceFailure): void {
            if ($forceFailure && $attachment->proposal_id !== null) {
                throw new \RuntimeException('Synthetic revision attachment persistence failure.');
            }
        });

        $thrown = null;
        try {
            app(KkprlProposalRevisionService::class)->requestRevision($submitted, 'Perbaiki lampiran.', $this->requester());
        } catch (\Throwable $exception) {
            $thrown = $exception;
        } finally {
            $forceFailure = false;
        }

        $this->assertInstanceOf(\RuntimeException::class, $thrown);
        Storage::disk('kkprl_private')->assertExists($originalAttachment->storage_path);
        $this->assertSame($filesBeforeRevision, Storage::disk('kkprl_private')->allFiles());
        $this->assertSame('submitted', $submitted->fresh()->status);
        $this->assertSame(0, $submitted->revisions()->count());
    }

    #[Test]
    public function revision_copy_rejects_a_legacy_attachment_on_a_non_private_disk(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        app(KkprlProposalDraftService::class)->save($proposal, $payload, 3);
        $submitted = app(KkprlProposalSubmissionService::class)->submit($proposal->fresh());

        \Illuminate\Support\Facades\DB::table('kkprl_proposal_attachments')->insert([
            'proposal_id' => $submitted->id,
            'chapter' => 'bag-1',
            'attachment_role' => 'supporting',
            'placement' => 'appendix',
            'original_name' => 'legacy.png',
            'storage_disk' => 'public',
            'storage_path' => 'legacy/attachment.png',
            'mime_type' => 'image/png',
            'size' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        app(KkprlProposalRevisionService::class)->requestRevision($submitted, 'Perbaiki lampiran.', $this->requester());
    }

    private function requester(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('Review:KkprlProposal', 'web'));

        return $user;
    }
}
