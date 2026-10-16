<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalLocked;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlApplicantPortal;
use App\Livewire\KkprlProposalWizard;
use App\Models\KkprlApplicant;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalDocument;
use App\Models\KkprlProposalReviewEvent;
use App\Models\User;
use App\Services\KkprlApplicantAuthService;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentDeletionService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalDocxRenderer;
use App\Services\KkprlProposalPdfRenderer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlProposalDocumentDeletionTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kkprl_private');
        Http::preventStrayRequests();
    }

    private function proposal(): KkprlProposal
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft();
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = KkprlPayloadFixture::value($field);
            }
        }
        $proposal->update(['payload' => $payload]);

        return $proposal->fresh();
    }

    private function document(KkprlProposal $proposal, string $format = 'pdf'): KkprlProposalDocument
    {
        return app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-2', [$format])->first();
    }

    public function test_deleting_one_file_frees_storage_and_keeps_snapshots_proposal_attachments_and_other_format(): void
    {
        $proposal = $this->proposal();
        $attachment = app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-2', UploadedFile::fake()->image('evidence.png'));
        $document = $this->document($proposal->fresh());
        $word = $this->document($proposal->fresh(), 'docx');
        $snapshot = $document->getRawOriginal('snapshot_payload_encrypted');
        $payload = $proposal->fresh()->getRawOriginal('payload_encrypted');

        app(KkprlProposalDocumentDeletionService::class)->delete($document->id);

        Storage::disk('kkprl_private')->assertMissing($document->storage_path);
        Storage::disk('kkprl_private')->assertExists($word->storage_path);
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
        $this->assertNotNull($document->fresh()->deleted_at);
        $this->assertSame($this->applicant->id, $document->fresh()->deleted_by_applicant_id);
        $this->assertSame($snapshot, $document->fresh()->getRawOriginal('snapshot_payload_encrypted'));
        $this->assertSame($payload, $proposal->fresh()->getRawOriginal('payload_encrypted'));
        $this->assertDatabaseCount('kkprl_proposal_documents', 2);
        $this->assertDatabaseCount('kkprl_proposal_attachments', 1);
        $event = KkprlProposalReviewEvent::where('event_type', 'document_deleted')->sole();
        $this->assertSame('applicant', $event->actor_type);
        $this->assertSame($this->applicant->id, $event->actor_id);
        $this->assertSame($document->id, $event->metadata['document_id']);
        $this->assertNotNull($event->created_at);
        $this->assertStringNotContainsString('document_id', $event->getRawOriginal('metadata'));
        $this->get(route('kkprl.proposal.document.download', $document))->assertNotFound();
        $this->get(route('kkprl.proposal.document.download', $word))->assertOk();
        Http::assertNothingSent();
    }

    public function test_deletion_is_idempotent_and_missing_files_can_be_removed_from_the_list(): void
    {
        $document = $this->document($this->proposal());
        Storage::disk('kkprl_private')->delete($document->storage_path);
        $service = app(KkprlProposalDocumentDeletionService::class);
        $service->delete($document->id);
        $service->delete($document->id);
        $this->assertNotNull($document->fresh()->deleted_at);
        $this->assertDatabaseCount('kkprl_proposal_review_events', 1);
    }

    public function test_pending_or_revoked_accounts_cannot_delete_via_service_or_livewire(): void
    {
        $document = $this->document($this->proposal());
        $component = Livewire::test(KkprlApplicantPortal::class);
        $this->applicant->forceFill(['approved_at' => null])->save();
        try {
            app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
            $this->fail('Pending account deleted a file.');
        } catch (ProposalAccessDenied) {
            $this->assertNull($document->fresh()->deleted_at);
        }
        $component->call('deleteDocument', $document->id)->assertForbidden();
        Livewire::test(KkprlApplicantPortal::class)->assertSee('PDF')->assertDontSee('Hapus dokumen');
        Storage::disk('kkprl_private')->assertExists($document->storage_path);
        $this->assertDatabaseCount('kkprl_proposal_review_events', 0);
    }

    public function test_cross_account_ids_are_rejected_and_staff_identity_is_not_an_applicant_bypass(): void
    {
        $document = $this->document($this->proposal());
        $other = KkprlApplicant::create(['email' => 'other@example.test', 'password' => 'Synthetic-other-123!']);
        $other->forceFill(['approved_at' => now()])->save();
        $this->signInApplicant($other->fresh());
        try {
            app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
            $this->fail('Cross account delete succeeded.');
        } catch (ModelNotFoundException) {
            $this->assertNull($document->fresh()->deleted_at);
        }
        Livewire::test(KkprlApplicantPortal::class)->call('deleteDocument', $document->id)->assertNotFound();
        app(KkprlApplicantAuthService::class)->logout();
        $this->actingAs(User::factory()->create());
        try {
            app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
            $this->fail('Staff identity deleted an applicant file.');
        } catch (ProposalAccessDenied) {
            Storage::disk('kkprl_private')->assertExists($document->storage_path);
        }
        $this->assertDatabaseCount('kkprl_proposal_review_events', 0);
    }

    public function test_submitted_and_needs_revision_archives_cannot_be_deleted(): void
    {
        $proposal = $this->proposal();
        $document = $this->document($proposal);
        foreach (['submitted', 'needs_revision'] as $status) {
            $proposal->update(['status' => $status]);
            try {
                app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
                $this->fail('Locked archive deleted.');
            } catch (ProposalLocked) {
                $this->assertNull($document->fresh()->deleted_at);
            }
            Livewire::test(KkprlApplicantPortal::class)->assertDontSee('Hapus dokumen')->call('deleteDocument', $document->id)->assertHasErrors('documents');
            $this->get(route('kkprl.proposal.document.download', $document))->assertOk();
        }
        Storage::disk('kkprl_private')->assertExists($document->storage_path);
        $this->assertDatabaseCount('kkprl_proposal_review_events', 0);
    }

    public function test_expired_reset_session_and_legacy_ticket_session_cannot_delete(): void
    {
        $document = $this->document($this->proposal());
        $component = Livewire::test(KkprlApplicantPortal::class);
        $this->applicant->forceFill(['session_version' => $this->applicant->fresh()->session_version + 1])->save();
        $component->call('deleteDocument', $document->id)->assertForbidden();
        session()->put('kkprl_proposal_access', ['proposal_id' => $document->proposal_id, 'last_activity' => now()->timestamp]);
        try {
            app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
            $this->fail('Legacy session deleted a document.');
        } catch (ProposalAccessDenied) {
            Storage::disk('kkprl_private')->assertExists($document->storage_path);
        }
        $this->assertNull($document->fresh()->deleted_at);
    }

    public function test_failed_storage_deletion_rolls_back_marker_and_audit(): void
    {
        $document = $this->document($this->proposal());
        $disk = Storage::disk('kkprl_private');
        $failing = \Mockery::mock($disk)->makePartial();
        $failing->shouldReceive('delete')->once()->with($document->storage_path)->andReturnFalse();
        Storage::shouldReceive('disk')->with('kkprl_private')->andReturn($failing);
        try {
            app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
            $this->fail('Failed storage delete reported success.');
        } catch (ValidationException) {
            $this->assertNull($document->fresh()->deleted_at);
        }
        $disk->assertExists($document->storage_path);
        $this->assertDatabaseCount('kkprl_proposal_review_events', 0);
    }

    public function test_public_disk_traversal_and_another_proposals_path_are_rejected(): void
    {
        $document = $this->document($this->proposal());
        $original = $document->storage_path;
        foreach ([['storage_disk' => 'public'], ['storage_path' => '../outside.pdf'], ['storage_path' => 'kkprl/proposals/999/999/documents/other.pdf']] as $tampered) {
            DB::table('kkprl_proposal_documents')->where('id', $document->id)->update($tampered);
            try {
                app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
                $this->fail('Untrusted path accepted.');
            } catch (ValidationException) {
                $this->assertNull($document->fresh()->deleted_at);
            }
            DB::table('kkprl_proposal_documents')->where('id', $document->id)->update(['storage_disk' => 'kkprl_private', 'storage_path' => $original]);
        }
        Storage::disk('kkprl_private')->assertExists($original);
        $this->assertDatabaseCount('kkprl_proposal_review_events', 0);
    }

    public function test_storage_exception_shows_retry_message_and_preserves_file_and_audit(): void
    {
        $document = $this->document($this->proposal());
        $disk = Storage::disk('kkprl_private');
        $failing = \Mockery::mock($disk)->makePartial();
        $failing->shouldReceive('delete')->once()->with($document->storage_path)
            ->andThrow(new \RuntimeException('Synthetic adapter failure'));
        Storage::shouldReceive('disk')->with('kkprl_private')->andReturn($failing);

        Livewire::test(KkprlApplicantPortal::class)->call('deleteDocument', $document->id)
            ->assertHasErrors('documents')->assertSee('Dokumen belum berhasil dihapus. Coba lagi.');
        $this->assertNull($document->fresh()->deleted_at);
        $disk->assertExists($document->storage_path);
        $this->assertDatabaseCount('kkprl_proposal_review_events', 0);
    }

    public function test_equal_generation_times_show_newest_document_first_in_portal_and_wizard(): void
    {
        $proposal = $this->proposal();
        $old = $this->document($proposal, 'docx');
        $new = $this->document($proposal, 'pdf');
        DB::table('kkprl_proposal_documents')->whereIn('id', [$old->id, $new->id])
            ->update(['generated_at' => now()->startOfMinute()]);

        foreach ([KkprlApplicantPortal::class, KkprlProposalWizard::class] as $component) {
            Livewire::test($component)->assertSeeInOrder([
                'generated-document-'.$new->id,
                'generated-document-'.$old->id,
            ], escape: false);
        }
    }

    public function test_portal_and_wizard_show_stale_documents_without_download_and_allow_deletion(): void
    {
        $proposal = $this->proposal();
        $document = $this->document($proposal);
        $payload = $proposal->payload;
        $payload['marine_space_use_narrative'] = 'Narasi yang sudah diperbarui.';
        $proposal->update(['payload' => $payload]);
        Livewire::test(KkprlApplicantPortal::class)->assertSee('Versi lama')->assertSee('Hapus dokumen')->assertDontSee(route('kkprl.proposal.document.download', $document));
        $component = Livewire::test(KkprlProposalWizard::class);
        $component->assertSee('Versi lama')->call('deleteDocument', $document->id)->assertHasNoErrors('documents')->assertSee('Dokumen dihapus')->assertSee('Belum ada dokumen');
        Livewire::test(KkprlApplicantPortal::class)->assertSee('Belum ada dokumen');
        $this->get(route('kkprl.proposal.document.download', $document))->assertNotFound();
        Storage::disk('kkprl_private')->assertMissing($document->storage_path);
    }

    public function test_portal_can_delete_an_owned_unselected_proposal_but_wizard_cannot_delete_another_proposal(): void
    {
        $first = $this->proposal();
        $document = $this->document($first);
        $this->proposal();
        Livewire::test(KkprlProposalWizard::class)->call('deleteDocument', $document->id)->assertNotFound();
        Storage::disk('kkprl_private')->assertExists($document->storage_path);
        Livewire::test(KkprlApplicantPortal::class)->call('deleteDocument', $document->id)->assertHasNoErrors('documents');
        Storage::disk('kkprl_private')->assertMissing($document->storage_path);
    }

    public function test_explicit_regeneration_restores_a_deleted_file_without_changing_snapshot_or_erasing_audit(): void
    {
        $proposal = $this->proposal();
        $document = $this->document($proposal);
        $snapshot = $document->getRawOriginal('snapshot_payload_encrypted');
        app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
        $this->get(route('kkprl.proposal.document.download', $document))->assertNotFound();
        $regenerated = $this->document($proposal);
        $this->assertSame($document->id, $regenerated->id);
        $this->assertNull($regenerated->deleted_at);
        $this->assertSame($snapshot, $regenerated->getRawOriginal('snapshot_payload_encrypted'));
        Storage::disk('kkprl_private')->assertExists($regenerated->storage_path);
        $this->get(route('kkprl.proposal.document.download', $regenerated))->assertOk();
        $this->assertDatabaseCount('kkprl_proposal_review_events', 1);
    }

    public function test_deletion_during_render_cannot_be_undone_by_the_in_flight_generation(): void
    {
        $proposal = $this->proposal();
        $document = $this->document($proposal);
        Storage::disk('kkprl_private')->delete($document->storage_path);
        $renderer = \Mockery::mock(app(KkprlProposalPdfRenderer::class))->makePartial();
        $renderer->shouldReceive('render')->once()->andReturnUsing(function () use ($document): string {
            app(KkprlProposalDocumentDeletionService::class)->delete($document->id);

            return '%PDF-synthetic';
        });
        $this->app->instance(KkprlProposalPdfRenderer::class, $renderer);
        try {
            $this->document($proposal);
            $this->fail('In-flight render restored a deleted file.');
        } catch (ValidationException) {
            $this->assertNotNull($document->fresh()->deleted_at);
        }
        Storage::disk('kkprl_private')->assertMissing($document->storage_path);
        $this->assertSame('generated', $document->fresh()->generation_status);
        $this->assertDatabaseCount('kkprl_proposal_review_events', 1);
    }

    public function test_additive_migration_preserves_existing_document_identity_and_bytes(): void
    {
        $document = $this->document($this->proposal());
        $before = $document->getRawOriginal();
        unset($before['deleted_at'], $before['deleted_by_applicant_id']);
        $migration = require database_path('migrations/2026_10_12_000001_add_document_deletion_metadata.php');
        $migration->down();
        $migration->up();
        $after = $document->fresh()->getRawOriginal();
        unset($after['deleted_at'], $after['deleted_by_applicant_id']);
        $this->assertSame($before, $after);
        $this->assertNull($document->fresh()->deleted_at);
        Storage::disk('kkprl_private')->assertExists($document->storage_path);
    }

    public function test_deletion_during_first_format_does_not_restore_a_deleted_second_format(): void
    {
        $proposal = $this->proposal();
        $word = $this->document($proposal, 'docx');
        $pdf = $this->document($proposal);
        Storage::disk('kkprl_private')->delete($word->storage_path);
        $renderer = \Mockery::mock(app(KkprlProposalDocxRenderer::class))->makePartial();
        $renderer->shouldReceive('render')->once()->andReturnUsing(function () use ($pdf): string {
            app(KkprlProposalDocumentDeletionService::class)->delete($pdf->id);

            return 'synthetic docx';
        });
        $this->app->instance(KkprlProposalDocxRenderer::class, $renderer);
        try {
            app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-2');
            $this->fail('In-flight batch restored the deleted second format.');
        } catch (ValidationException) {
            $this->assertNotNull($pdf->fresh()->deleted_at);
        }
        Storage::disk('kkprl_private')->assertMissing($pdf->storage_path);
    }

    public function test_shared_file_reference_is_rejected_without_deleting_either_document(): void
    {
        $proposal = $this->proposal();
        $document = $this->document($proposal);
        $payload = $proposal->payload;
        $payload['marine_space_use_narrative'] = 'Versi kedua.';
        $proposal->update(['payload' => $payload]);
        $other = $this->document($proposal->fresh());
        DB::table('kkprl_proposal_documents')->where('id', $other->id)->update(['storage_path' => $document->storage_path]);
        try {
            app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
            $this->fail('Shared file was deleted.');
        } catch (ValidationException) {
            $this->assertNull($document->fresh()->deleted_at);
            $this->assertNull($other->fresh()->deleted_at);
        }
        Storage::disk('kkprl_private')->assertExists($document->storage_path);
        $this->assertDatabaseCount('kkprl_proposal_review_events', 0);
    }

    public function test_resolved_storage_target_cannot_alias_another_artifact(): void
    {
        $document = $this->document($this->proposal());
        $disk = Storage::disk('kkprl_private');
        $disk->put('different-artifact.pdf', 'Do not delete');
        $alias = \Mockery::mock($disk)->makePartial();
        $alias->shouldReceive('path')->with($document->storage_path)->andReturn($disk->path('different-artifact.pdf'));
        Storage::shouldReceive('disk')->with('kkprl_private')->andReturn($alias);
        try {
            app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
            $this->fail('Resolved target escaped its exact artifact path.');
        } catch (ValidationException) {
            $this->assertNull($document->fresh()->deleted_at);
        }
        $disk->assertExists('different-artifact.pdf');
        $disk->assertExists($document->storage_path);
    }

    public function test_approval_is_rechecked_after_acquiring_the_proposal_lock(): void
    {
        $document = $this->document($this->proposal());
        $revoke = true;
        $accountId = $this->applicant->id;
        KkprlProposal::retrieved(function () use (&$revoke, $accountId): void {
            if ($revoke) {
                $revoke = false;
                DB::table('kkprl_applicants')->where('id', $accountId)->update(['approved_at' => null]);
            }
        });
        try {
            app(KkprlProposalDocumentDeletionService::class)->delete($document->id);
            $this->fail('Approval revocation bypassed persistence checkpoint.');
        } catch (ProposalAccessDenied) {
            $this->assertNull($document->fresh()->deleted_at);
        }
        Storage::disk('kkprl_private')->assertExists($document->storage_path);
        $this->assertDatabaseCount('kkprl_proposal_review_events', 0);
    }
}
