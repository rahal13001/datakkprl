<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalLocked;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlProposalWizard;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalReviewEvent;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlProposalWizardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function wizard_restores_the_current_draft_and_shows_progress(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft([
            'phone' => '0812-3456-7890',
        ]);

        Livewire::test(KkprlProposalWizard::class)
            ->assertSet('proposalId', $proposal->id)
            ->assertSet('currentStep', 1)
            ->assertSee('Kelengkapan proposal')
            ->assertSee('Bab 1')
            ->assertSee('Mengunggah...');
    }

    #[Test]
    public function proposal_scope_property_cannot_be_tampered_with_by_livewire(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $other = $access->startDraft(['phone' => '0812-1111-1111']);
        $access->grantAccess($proposal);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(KkprlProposalWizard::class)->set('proposalId', $other->id);
    }

    #[Test]
    public function expired_wizard_session_returns_a_safe_access_message(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $access->startDraft(['phone' => '0812-3456-7890']);
        $component = Livewire::test(KkprlProposalWizard::class);
        $access->forget();

        $component
            ->call('saveDraft')
            ->assertSee('Sesi akses proposal berakhir. Verifikasi ulang nomor tiket dan nomor HP.');
    }

    #[Test]
    public function expired_wizard_session_does_not_render_proposal_data_on_refresh(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $access->startDraft(['phone' => '0812-3456-7890']);
        $component = Livewire::test(KkprlProposalWizard::class);
        $access->forget();

        $component
            ->call('previous')
            ->assertSee('Sesi akses proposal berakhir. Verifikasi ulang nomor tiket dan nomor HP.')
            ->assertDontSee('Kelengkapan proposal')
            ->assertDontSee('Nama Pemohon Lengkap');
    }

    #[Test]
    public function active_revision_shows_the_petugas_revision_note(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $original = $access->startDraft(['phone' => '0812-3456-7890']);
        $original->update(['status' => 'needs_revision']);
        $revision = KkprlProposal::create([
            'root_proposal_id' => $original->root_proposal_id,
            'revision_of_id' => $original->id,
            'revision_number' => 1,
            'revision_label' => 'rev-1',
            'ticket_number' => $original->ticket_number,
            'phone' => '0812-3456-7890',
            'status' => 'draft',
        ]);
        KkprlProposalReviewEvent::create([
            'proposal_id' => $original->id,
            'event_type' => 'needs_revision',
            'actor_type' => 'staff',
            'reason' => 'Perbaiki uraian lokasi.',
            'created_at' => now(),
        ]);
        $access->grantAccess($revision);

        Livewire::test(KkprlProposalWizard::class)
            ->assertSee('Catatan Revisi Petugas')
            ->assertSee('Perbaiki uraian lokasi.');
    }

    #[Test]
    public function save_button_and_autosave_keep_one_draft_record(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft([
            'phone' => '0812-3456-7890',
        ]);

        $component = Livewire::test(KkprlProposalWizard::class)
            ->set('payload.bag-1.province', 'Bali')
            ->call('saveDraft')
            ->assertSet('saved', true);

        $this->assertSame(1, $proposal->newQuery()->count());
        $this->assertSame('Bali', $proposal->fresh()->payload['bag-1']['province']);
        $this->assertNotSame('', $component->get('lastSavedAt'));
    }

    #[Test]
    public function map_edits_are_saved_and_invalid_coordinates_block_submission(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $component = Livewire::test(KkprlProposalWizard::class)
            ->call('updateCoordinates', '[{"lat_dd":"999","lng_dd":"106.8"}]', KkprlPayloadFixture::value('coordinates'), 'point')
            ->assertSet('chapterProgress.bag-1.status', 'error')
            ->call('submitProposal')
            ->assertHasErrors('fields.coordinates');

        $this->assertSame('point', $proposal->fresh()->payload['bag-1']['shape_type']);
        $this->assertSame('[{"lat_dd":"999","lng_dd":"106.8"}]', $proposal->fresh()->payload['bag-1']['coordinates_raw']);
        $this->assertSame('draft', $proposal->fresh()->status);
        $this->assertDatabaseCount('kkprl_proposal_documents', 0);
    }

    #[Test]
    public function an_expired_session_cannot_edit_map_coordinates(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $component = Livewire::test(KkprlProposalWizard::class);
        $access->forget();

        $this->expectException(ProposalAccessDenied::class);
        try {
            $component->call('updateCoordinates', '[]', '', 'point');
        } finally {
            $this->assertEmpty($proposal->fresh()->payload);
        }
    }

    #[Test]
    public function a_locked_proposal_cannot_edit_map_coordinates(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $component = Livewire::test(KkprlProposalWizard::class);
        $proposal->update(['status' => 'submitted']);

        $this->expectException(ProposalLocked::class);
        try {
            $component->call('updateCoordinates', '[]', '', 'point');
        } finally {
            $this->assertEmpty($proposal->fresh()->payload);
        }
    }

    #[Test]
    public function inactive_conditional_chapters_are_not_rendered(): void
    {
        app(KkprlProposalAccessService::class)->startDraft([
            'phone' => '0812-3456-7890',
        ]);

        Livewire::test(KkprlProposalWizard::class)
            ->assertSee('Reklamasi')
            ->assertSee('<option value="">-- Pilih --</option>', false)
            ->assertDontSee('Bab 4 — Interaksi Cerdas Bersama AI')
            ->set('payload.includes_reclamation', true)
            ->call('saveDraft')
            ->call('next')
            ->call('next')
            ->call('next')
            ->assertSee('Bab 4 — Interaksi Cerdas Bersama AI');
    }

    #[Test]
    public function boolean_choices_are_restored_as_select_values_and_keep_the_real_chapter_number(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $proposal->update(['payload' => [
            'includes_reclamation' => false,
            'land_relation' => 'adjacent',
            'has_existing_permits' => true,
        ]]);

        Livewire::test(KkprlProposalWizard::class)
            ->assertSet('payload.includes_reclamation', '0')
            ->assertSet('payload.has_existing_permits', '1')
            ->assertSee('Bab 5')
            ->assertDontSee('Bab 4')
            ->assertSet('relevantChapters', ['bag-1', 'bag-2', 'bag-3', 'bag-5'])
            ->set('currentStep', 4)
            ->assertSet('attachmentChapter', 'bag-5')
            ->assertSee('Bab 5 — Interaksi Cerdas Bersama AI');
    }

    #[Test]
    public function stale_conditional_documents_are_not_rendered_after_condition_is_disabled(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = KkprlPayloadFixture::value($field);
            }
        }
        $payload['includes_reclamation'] = true;
        $payload['bag-4'] = array_fill_keys(ProposalProgress::requiredFields('bag-4'), 'Terisi');
        $payload['bag-4']['reclamation_schedule_rows'] = [[
            'activity' => 'Pengerukan',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-01',
            'notes' => 'Tahap awal',
        ]];
        $proposal->update(['payload' => $payload]);
        app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-4', ['pdf']);

        $payload['includes_reclamation'] = false;
        $proposal->update(['payload' => $payload]);

        Livewire::test(KkprlProposalWizard::class)
            ->assertDontSee('Bab 4 — PDF');
    }

    #[Test]
    public function bag_five_renders_only_the_selected_subsection(): void
    {
        app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);

        Livewire::test(KkprlProposalWizard::class)
            ->set('payload.land_relation', 'adjacent')
            ->set('payload.has_existing_permits', '0')
            ->call('saveDraft')
            ->call('next')->call('next')->call('next')
            ->assertSee('Jenis bukti kepemilikan/penguasaan')
            ->assertDontSee('Instansi penerbit');
    }

    #[Test]
    public function wizard_uploads_attachment_with_selected_placement(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);

        Livewire::test(KkprlProposalWizard::class)
            ->set('pendingFiles', [UploadedFile::fake()->image('site-plan.png')])
            ->set('attachmentPlacement', 'inline')
            ->set('attachmentAnchor', 'site_plan_description')
            ->call('uploadAttachments')
            ->assertSet('attachmentMessage', 'Lampiran berhasil diunggah.');

        $attachment = $proposal->attachments()->first();
        $this->assertNotNull($attachment);
        $this->assertSame('inline', $attachment->placement);
        $this->assertSame('site_plan_description', $attachment->anchor_key);
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
    }

    #[Test]
    public function wizard_distinguishes_attachment_count_quota_error(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $service = app(KkprlProposalAttachmentService::class);
        for ($index = 0; $index < 10; $index++) {
            $service->store($proposal, 'bag-1', UploadedFile::fake()->image("file-{$index}.png"));
        }

        Livewire::test(KkprlProposalWizard::class)
            ->set('pendingFiles', [UploadedFile::fake()->image('eleventh.png')])
            ->call('uploadAttachments')
            ->assertSee('Jumlah lampiran melebihi maksimum 10 file per bab.');
    }

    #[Test]
    public function wizard_can_delete_and_replace_an_attachment(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $attachment = app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->image('old.png'),
        );

        Livewire::test(KkprlProposalWizard::class)
            ->set('replacementAttachmentId', $attachment->id)
            ->set('replacementFile', UploadedFile::fake()->image('new.png'))
            ->call('replaceAttachment')
            ->assertSet('attachmentMessage', 'Lampiran diganti.')
            ->call('deleteAttachment', $attachment->id)
            ->assertSet('attachmentMessage', 'Lampiran dihapus.');

        $this->assertSoftDeleted('kkprl_proposal_attachments', ['id' => $attachment->id]);
    }

    #[Test]
    public function locked_proposal_upload_returns_a_safe_validation_message(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $proposal->update(['status' => 'submitted']);

        Livewire::test(KkprlProposalWizard::class)
            ->set('pendingFiles', [UploadedFile::fake()->image('locked.png')])
            ->call('uploadAttachments')
            ->assertSee('Proposal sudah terkunci dan lampiran tidak dapat diubah.');
    }

    #[Test]
    public function complete_wizard_can_submit_and_then_becomes_locked(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = KkprlPayloadFixture::value($field);
            }
        }

        Livewire::test(KkprlProposalWizard::class)
            ->set('payload', $payload)
            ->set('currentStep', 3)
            ->assertSee('Membuat...')
            ->assertSee('Memproses...')
            ->call('submitProposal')
            ->assertSet('locked', true)
            ->assertSee('Proposal Terkunci (Sedang Ditinjau)');

        $this->assertSame('submitted', $proposal->fresh()->status);
    }
}
