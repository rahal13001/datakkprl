<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlAiChat;
use App\Livewire\KkprlApplicantPortal;
use App\Livewire\KkprlProposalWizard;
use App\Models\KkprlApplicant;
use App\Models\KkprlProposal;
use App\Models\User;
use App\Services\KkprlAiService;
use App\Services\KkprlApplicantAdministrationService;
use App\Services\KkprlApplicantAuthService;
use App\Services\KkprlBathymetryService;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalDraftService;
use App\Services\KkprlProposalRevisionService;
use App\Services\KkprlProposalSnapshotService;
use App\Services\KkprlProposalSubmissionService;
use App\Services\KkprlSeabedProfileService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlApplicantBoundaryTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    private function draft(): KkprlProposal
    {
        return app(KkprlProposalAccessService::class)->startDraft();
    }

    private function payload(): array
    {
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = KkprlPayloadFixture::value($field);
            }
        }

        return $payload;
    }

    public static function actions(): array
    {
        $wizard = ['saveDraft' => [], 'next' => [], 'previous' => [], 'addScheduleRow' => [], 'removeScheduleRow' => [0],
            'uploadAttachments' => [], 'deleteAttachment' => [1], 'replaceAttachment' => [],
            'submitProposal' => [], 'generateChapterDocuments' => ['bag-1'], 'updateCoordinates' => ['[]', '', 'point'],
            '_startUpload' => ['pendingFiles', [], true], '_finishUpload' => ['pendingFiles', [], true], '_removeUpload' => ['pendingFiles', 'forged']];
        $chat = ['sendMessage' => [], 'confirmAndSave' => [], 'addScheduleRow' => [], 'removeScheduleRow' => [0],
            'uploadEvidence' => [], 'analyzeEvidence' => [1], 'deleteEvidence' => [1], 'uploadTideData' => [],
            'calculateTideData' => [1], 'analyzeTideData' => [1], 'deleteTideData' => [1], 'generateTideChart' => [1],
            'generateBathymetry' => [], 'generateSeabedProfile' => [], 'loadChatHistory' => [],
            '_startUpload' => ['evidenceFile', [], false], '_finishUpload' => ['evidenceFile', [], false]];
        $cases = [];
        foreach ([KkprlProposalWizard::class => $wizard, KkprlAiChat::class => $chat] as $component => $actions) {
            foreach ($actions as $action => $args) {
                $cases[$component.' '.$action] = [$component, $action, $args];
            }
        }

        return $cases;
    }

    #[DataProvider('actions')]
    public function test_pending_snapshot_denies_every_builder_action_without_external_request_or_mutation(string $class, string $action, array $args): void
    {
        Http::preventStrayRequests();
        $proposal = $this->draft();
        $component = Livewire::test($class, $class === KkprlAiChat::class ? ['proposalId' => $proposal->id, 'chapter' => 'bag-3'] : []);
        $before = $proposal->fresh()->getAttributes();
        $this->applicant->forceFill(['approved_at' => null])->save();
        $component->call($action, ...$args)->assertForbidden();
        $this->assertSame($before, $proposal->fresh()->getAttributes());
        $this->assertDatabaseCount('kkprl_proposal_chats', 0);
        $this->assertDatabaseCount('kkprl_proposal_attachments', 0);
        $this->assertDatabaseCount('kkprl_proposal_documents', 0);
        Http::assertNothingSent();
    }

    public function test_pending_cannot_call_portal_creation_or_selection_directly(): void
    {
        $proposal = $this->draft();
        $this->applicant->forceFill(['approved_at' => null])->save();
        Livewire::test(KkprlApplicantPortal::class)->call('createProposal')->assertForbidden();
        Livewire::test(KkprlApplicantPortal::class)->call('openProposal', $proposal->id)->assertForbidden();
        $this->assertDatabaseCount('kkprl_proposals', 1);
    }

    public function test_pending_services_deny_upload_delete_replace_generation_submission_and_external_analysis(): void
    {
        Storage::fake('kkprl_private');
        Http::preventStrayRequests();
        $proposal = $this->draft();
        $attachments = app(KkprlProposalAttachmentService::class);
        $attachment = $attachments->store($proposal, 'bag-1', UploadedFile::fake()->image('private.png'));
        $this->applicant->forceFill(['approved_at' => null])->save();
        $operations = [
            fn () => $attachments->store($proposal, 'bag-1', UploadedFile::fake()->image('blocked.png')),
            fn () => $attachments->delete($proposal, $attachment),
            fn () => $attachments->replace($proposal, $attachment, UploadedFile::fake()->image('blocked.png')),
            fn () => app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-1'),
            fn () => app(KkprlProposalSubmissionService::class)->submit($proposal),
            fn () => app(KkprlAiService::class)->chat([], 'Synthetic'),
            fn () => app(KkprlAiService::class)->analyzeImage('synthetic', 'image/png', 'Synthetic'),
            fn () => app(KkprlBathymetryService::class)->fetch([[-0.48, 130.88]], 1),
            fn () => app(KkprlSeabedProfileService::class)->fetch(-0.48, 130.88, -0.49, 130.89),
        ];
        foreach ($operations as $operation) {
            try {
                $operation();
                $this->fail('Pending service operation succeeded.');
            } catch (ProposalAccessDenied) {
                $this->assertDatabaseCount('kkprl_proposal_attachments', 1);
                $this->assertDatabaseCount('kkprl_proposal_documents', 0);
            }
        }
        $this->get(route('kkprl.proposal.attachment.download', $attachment))->assertNotFound();
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
        Http::assertNothingSent();
    }

    public function test_upload_url_obtained_before_revocation_cannot_store_a_file_after_revocation(): void
    {
        Storage::fake('local');
        $proposal = $this->draft();
        $url = URL::temporarySignedRoute('kkprl.applicant.upload', now()->addMinutes(5), [
            'proposal' => $proposal->id, 'applicant' => $this->applicant->id, 'session_version' => 1,
        ]);
        $this->applicant->forceFill(['approved_at' => null])->save();
        $this->post($url, ['files' => [UploadedFile::fake()->image('blocked.png')]])->assertForbidden();
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_revocation_during_gmrt_request_does_not_store_artifacts_or_chat(): void
    {
        Storage::fake('kkprl_private');
        $proposal = $this->draft();
        $proposal->update(['payload' => ['bag-1' => ['coordinates_raw' => '[{"lat_dd":"-0.48","lng_dd":"130.88"}]', 'shape_type' => 'point']]]);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => function () {
            $this->applicant->forceFill(['approved_at' => null])->save();

            return Http::response(KkprlBathymetryTest::grid());
        }]);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('evidenceField', 'bathymetry')->call('generateBathymetry')->assertForbidden();
        Http::assertSentCount(1);
        $this->assertDatabaseCount('kkprl_proposal_attachments', 0);
        $this->assertDatabaseCount('kkprl_proposal_chats', 0);
        $this->assertEmpty(Storage::disk('kkprl_private')->allFiles());
    }

    public function test_revocation_during_pdf_rendering_prevents_final_and_failed_writes(): void
    {
        Storage::fake('kkprl_private');
        $proposal = $this->draft();
        $proposal->update(['payload' => $this->payload()]);
        $renderer = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $renderer->shouldReceive('setPaper')->andReturnSelf();
        $renderer->shouldReceive('output')->once()->andReturnUsing(function () {
            $this->applicant->forceFill(['approved_at' => null])->save();

            return '%PDF synthetic';
        });
        Pdf::shouldReceive('loadView')->once()->andReturn($renderer);
        try {
            app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf']);
            $this->fail('Revoked render persisted.');
        } catch (ProposalAccessDenied) {
            $this->assertDatabaseCount('kkprl_proposal_documents', 0);
            $this->assertEmpty(Storage::disk('kkprl_private')->allFiles());
        }
    }

    public function test_approval_metadata_changes_do_not_change_document_snapshot(): void
    {
        Storage::fake('kkprl_private');
        $proposal = $this->draft();
        $proposal->update(['payload' => $this->payload()]);
        $snapshots = app(KkprlProposalSnapshotService::class);
        $before = $snapshots->capture($proposal->fresh(), 'bag-1');
        $document = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf'])->first();
        $this->applicant->forceFill(['email' => 'changed@example.test', 'approved_at' => null])->save();
        $this->assertSame($before->snapshotHash, $snapshots->capture($proposal->fresh(), 'bag-1')->snapshotHash);
        $this->get(route('kkprl.proposal.document.download', $document))->assertOk();
        Storage::disk('kkprl_private')->delete($document->storage_path);
        $this->get(route('kkprl.proposal.document.download', $document))->assertNotFound();
    }

    public function test_applicant_credentials_and_sessions_cannot_enter_staff_panel_or_api(): void
    {
        $this->assertNull(Auth::guard('web')->user());
        $this->assertFalse(Auth::guard('web')->attempt(['email' => $this->applicant->email, 'password' => 'Synthetic-fixture-123!']));
        $this->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-applicants')->assertRedirect();
        $this->getJson('/api/mobile/v1/me')->assertUnauthorized();
        $this->postJson('/api/mobile/v1/auth/login', ['email' => $this->applicant->email, 'password' => 'Synthetic-fixture-123!', 'device_name' => 'Synthetic', 'installation_id' => '6cfadbec-29e0-47c8-8f96-a2798d0eb39b', 'platform' => 'android', 'app_version' => '1.0.0', 'build_number' => 1])->assertUnprocessable()->assertJsonPath('errors.email.0', 'The supplied credentials are invalid.');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_revision_inherits_root_owner_and_pending_cannot_open_it(): void
    {
        $proposal = $this->draft();
        $proposal->update(['status' => 'submitted']);
        $actor = User::factory()->create();
        $actor->givePermissionTo(Permission::findOrCreate('Review:KkprlProposal', 'web'));
        $revision = app(KkprlProposalRevisionService::class)->requestRevision($proposal, 'Perbaiki data.', $actor);
        $this->assertSame($proposal->applicant_id, $revision->applicant_id);
        $this->applicant->forceFill(['approved_at' => null])->save();
        Livewire::test(KkprlApplicantPortal::class)->call('openProposal', $revision->id)->assertForbidden();
    }

    public function test_reset_rejects_old_credentials_and_every_old_session_version(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(Permission::findOrCreate('ResetPassword:KkprlApplicant', 'web'));
        app(KkprlApplicantAdministrationService::class)->resetPassword($this->applicant, $actor, 'Synthetic-reset-123!', 'Synthetic-reset-123!');
        foreach ([1, 0] as $oldVersion) {
            Auth::guard('kkprl_applicant')->login($this->applicant);
            session()->put(KkprlApplicantAuthService::VERSION_KEY, $oldVersion);
            $this->assertNull(app(KkprlApplicantAuthService::class)->current());
        }
        try {
            app(KkprlApplicantAuthService::class)->login($this->applicant->email, 'Synthetic-fixture-123!');
            $this->fail('Old password authenticated.');
        } catch (ValidationException) {
            $this->assertNull(app(KkprlApplicantAuthService::class)->current());
        }
        app(KkprlApplicantAuthService::class)->login($this->applicant->email, 'Synthetic-reset-123!');
        $this->assertNotNull(app(KkprlApplicantAuthService::class)->current());
    }

    public function test_reset_during_ai_request_prevents_chat_or_proposal_write(): void
    {
        $proposal = $this->draft();
        $actor = User::factory()->create();
        $actor->givePermissionTo(Permission::findOrCreate('ResetPassword:KkprlApplicant', 'web'));
        $this->mock(KkprlAiService::class, function ($mock) use ($actor) {
            $mock->shouldReceive('chat')->once()->andReturnUsing(function () use ($actor) {
                app(KkprlApplicantAdministrationService::class)->resetPassword($this->applicant, $actor, 'Synthetic-reset-123!', 'Synthetic-reset-123!');

                return 'Narasi setelah reset.';
            });
        });
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('userInput', 'Ulas perairan')->call('sendMessage')->assertForbidden();
        $this->assertDatabaseCount('kkprl_proposal_chats', 0);
        $this->assertEmpty($proposal->fresh()->payload);
    }

    public function test_approved_foreign_account_cannot_mutate_services_or_mint_upload_url(): void
    {
        Storage::fake('kkprl_private');
        $proposal = $this->draft();
        $attachment = app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-1', UploadedFile::fake()->image('owner.png'));
        $other = KkprlApplicant::create(['email' => 'foreign@example.test', 'password' => 'Synthetic-other-123!']);
        $other->forceFill(['approved_at' => now()])->save();
        $this->signInApplicant($other->fresh());
        $ops = [
            fn () => app(KkprlProposalDraftService::class)->save($proposal, [], 1),
            fn () => app(KkprlProposalAttachmentService::class)->delete($proposal, $attachment),
            fn () => app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-1'),
            fn () => app(KkprlProposalSubmissionService::class)->submit($proposal),
        ];
        foreach ($ops as $operation) {
            try {
                $operation();
                $this->fail('Foreign service succeeded.');
            } catch (ProposalAccessDenied) {
                $this->assertEmpty($proposal->fresh()->payload);
            }
        }
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])->assertForbidden();
        $this->get(route('kkprl.proposal.attachment.download', $attachment))->assertNotFound();
        $this->assertDatabaseCount('kkprl_proposal_attachments', 1);
    }
}
