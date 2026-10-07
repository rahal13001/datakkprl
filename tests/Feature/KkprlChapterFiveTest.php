<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlAiChat;
use App\Livewire\KkprlProposalWizard;
use App\Models\KkprlProposal;
use App\Services\KkprlAiService;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\KkprlApplicantFixture;
use Tests\TestCase;

class KkprlChapterFiveTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kkprl_private');
        Http::preventStrayRequests();
    }

    private function draft(bool $adjacent = false, bool $hasPermits = true): KkprlProposal
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $proposal->update(['payload' => ['land_relation' => $adjacent ? 'adjacent' : 'not_adjacent', 'has_existing_permits' => $hasPermits,
            'bag-1' => ['province' => 'Papua Barat Daya'], 'bag-3' => ['bathymetry' => 'Narasi batimetri dipertahankan.'],
            'bag-5' => ['permit_type' => 'Data izin lama.', 'permit_number' => 'OLD-PERMIT', 'land_notes' => 'Koreksi lahan.']]]);

        return $proposal;
    }

    #[Test]
    public function absence_of_permits_is_reviewable_then_disables_only_the_permit_section_on_confirmation(): void
    {
        $proposal = $this->draft(adjacent: true);
        $component = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->assertSet('existingPermitsChoice', '1')->set('existingPermitsChoice', '0')->assertHasNoErrors()
            ->assertSee('Belum memiliki izin pendukung')->assertDontSee('Instansi penerbit')
            ->assertSee('Jenis bukti kepemilikan/penguasaan');
        $this->assertTrue($proposal->fresh()->payload['has_existing_permits']);
        $component->call('confirmAndSave')->assertDispatched('draft-saved')->assertHasNoErrors();
        $saved = $proposal->fresh()->payload;
        $this->assertFalse($saved['has_existing_permits']);
        $this->assertSame('OLD-PERMIT', $saved['bag-5']['permit_number']);
        $this->assertSame('Koreksi lahan.', $saved['bag-5']['land_notes']);
        $this->assertSame('Narasi batimetri dipertahankan.', $saved['bag-3']['bathymetry']);
        $this->assertSame('Papua Barat Daya', $saved['bag-1']['province']);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->assertSet('existingPermitsChoice', '0')->assertDontSee('Instansi penerbit');
        Http::assertNothingSent();
    }

    #[Test]
    public function pending_choice_survives_resume_and_switching_back_restores_manual_permit_details(): void
    {
        $proposal = $this->draft(adjacent: true);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->set('previewData.permit_number', 'KOREKSI-123')->set('existingPermitsChoice', '0');
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->assertSet('existingPermitsChoice', '0')->set('existingPermitsChoice', '1')
            ->assertSet('previewData.permit_number', 'KOREKSI-123')->assertSee('Instansi penerbit');
        $this->assertTrue($proposal->fresh()->payload['has_existing_permits']);
    }

    #[Test]
    public function chapter_five_is_skipped_when_neither_land_nor_permits_is_relevant(): void
    {
        $proposal = $this->draft();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->set('existingPermitsChoice', '0')->assertSee('Bab 5 dapat dilewati')->call('confirmAndSave')->assertHasNoErrors();
        $this->assertNotContains('bag-5', app(ProposalProgress::class)->evaluate($proposal->fresh()->payload)->relevantChapters);
        Livewire::test(KkprlProposalWizard::class)->assertSet('relevantChapters', ['bag-1', 'bag-2', 'bag-3']);
    }

    #[Test]
    public function real_permits_without_optional_details_can_be_saved_and_exported(): void
    {
        $proposal = $this->draft();
        $proposal->update(['payload' => ['has_existing_permits' => true, 'land_relation' => 'not_adjacent']]);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->set('previewData.permit_type', 'Izin usaha')->set('previewData.permit_year', '2025')
            ->set('previewData.permit_status', 'Berlaku')->call('confirmAndSave')->assertHasNoErrors();
        $this->assertSame('complete', app(ProposalProgress::class)->evaluate($proposal->fresh()->payload)->chapters['bag-5']['status']);
        foreach (app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-5') as $document) {
            $this->assertSame('generated', $document->generation_status);
        }
    }

    #[Test]
    public function choice_is_validated_and_cannot_bypass_scope_access_or_lock(): void
    {
        $proposal = $this->draft(adjacent: true);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->set('existingPermitsChoice', 'unknown')->assertHasErrors('existingPermitsChoice')->call('confirmAndSave')->assertHasErrors('existingPermitsChoice');
        $this->assertTrue($proposal->fresh()->payload['has_existing_permits']);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('existingPermitsChoice', '0')->assertStatus(403);
        $component = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5']);
        $proposal->update(['status' => 'submitted']);
        $component->set('existingPermitsChoice', '0')->assertStatus(409);
    }

    #[Test]
    public function ai_prompt_explains_optional_fields_and_uses_the_pending_availability_choice(): void
    {
        $proposal = $this->draft(adjacent: true);
        $this->mock(KkprlAiService::class, function ($mock): void {
            $mock->shouldReceive('chat')->once()->withArgs(fn ($messages, $prompt) => str_contains($prompt, 'belum memiliki izin')
                && str_contains($prompt, 'Jangan meminta nomor izin') && str_contains($prompt, 'jika tersedia'))
                ->andReturn('Mari lengkapi status lahan Anda.');
        });
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->set('existingPermitsChoice', '0')->set('userInput', 'Saya belum memiliki izin.')->call('sendMessage')
            ->assertSee('Mari lengkapi status lahan Anda.')->assertHasNoErrors();
    }

    #[Test]
    public function optional_details_survive_legacy_flat_snapshot_and_nested_snapshot(): void
    {
        $proposal = $this->draft();
        foreach ([false, true] as $nested) {
            $details = ['permit_type' => 'Izin usaha', 'permit_year' => '2025', 'permit_status' => 'Berlaku',
                'permit_number' => 'IZIN-123', 'permit_issuer' => 'Instansi penerbit sesuai dokumen'];
            $payload = ['has_existing_permits' => true, 'land_relation' => 'not_adjacent'];
            $payload = $nested ? $payload + ['bag-5' => $details] : $payload + $details;
            $proposal->update(['payload' => $payload]);
            $snapshot = app(KkprlProposalSnapshotService::class)->capture($proposal->fresh(), 'bag-5');
            $this->assertSame('IZIN-123', $snapshot->payload['payload']['bag-5']['permit_number']);
            $this->assertSame('Instansi penerbit sesuai dokumen', $snapshot->payload['payload']['bag-5']['permit_issuer']);
        }
    }

    #[Test]
    public function ai_cannot_restore_inactive_permit_details_after_the_user_selects_no_permits(): void
    {
        $proposal = $this->draft(adjacent: true);
        $this->mock(KkprlAiService::class, fn ($mock) => $mock->shouldReceive('chat')->once()
            ->andReturn('{"permit_number":"AI-MUST-NOT-RESTORE"}'));
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->set('existingPermitsChoice', '0')->set('userInput', 'Lengkapi keterangan lahan.')
            ->call('sendMessage')->assertSet('previewData.permit_number', 'OLD-PERMIT')
            ->assertSee('Saya belum berhasil menampilkan draft dengan lengkap.');
    }

    #[Test]
    public function stale_preview_choice_does_not_undo_a_new_decision_from_chapter_one(): void
    {
        $proposal = $this->draft(adjacent: true);
        $component = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5']);
        $component->set('existingPermitsChoice', '0')->call('confirmAndSave');
        $payload = $proposal->fresh()->payload;
        $payload['has_existing_permits'] = true;
        $proposal->update(['payload' => $payload]);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->assertSet('existingPermitsChoice', '1')->assertSee('Instansi penerbit');
        // An older tab that never changed its saved selection must keep the current Chapter 1 decision.
        $older = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5']);
        $payload['has_existing_permits'] = false;
        $proposal->update(['payload' => $payload]);
        $older->call('confirmAndSave')->assertHasNoErrors();
        $this->assertFalse($proposal->fresh()->payload['has_existing_permits']);
    }

    #[Test]
    public function inactive_permit_attachments_are_preserved_but_do_not_block_land_export(): void
    {
        $proposal = $this->draft(adjacent: true);
        $payload = $proposal->payload;
        $payload['bag-5'] += ['land_status' => 'Berhimpitan', 'land_evidence_type' => 'Sertifikat', 'land_acquisition_year' => '2024'];
        $proposal->update(['payload' => $payload]);
        $attachment = app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-5', UploadedFile::fake()->image('izin-lama.png'),
            ['placement' => 'inline', 'anchor_key' => 'permit_document']);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-5'])
            ->set('existingPermitsChoice', '0')->call('confirmAndSave')->assertHasNoErrors();
        $snapshot = app(KkprlProposalSnapshotService::class)->capture($proposal->fresh(), 'bag-5');
        $this->assertSame([], $snapshot->attachments);
        $this->assertSame(1, $proposal->attachments()->count());
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
        $this->assertArrayNotHasKey('permit_number', $snapshot->payload['payload']['bag-5']);
        foreach (app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-5') as $document) {
            $this->assertSame('generated', $document->generation_status);
        }
    }
}
