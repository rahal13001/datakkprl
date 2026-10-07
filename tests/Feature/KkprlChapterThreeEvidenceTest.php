<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlAiChat;
use App\Models\KkprlProposal;
use App\Services\KkprlAiService;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlChapterThreeEvidenceTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kkprl_private');
        config(['services.gemini.key' => 'synthetic-test-key']);
        Http::preventStrayRequests();
    }

    private function draft(): KkprlProposal
    {
        return app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
    }

    private function upload($proposal)
    {
        return Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('evidencePeriod', '2025')
            ->set('evidenceFile', UploadedFile::fake()->image('mangrove.png', 640, 480))
            ->call('uploadEvidence')
            ->assertHasNoErrors();
    }

    #[Test]
    public function upload_keeps_source_and_private_image_without_confirming_a_narrative(): void
    {
        $proposal = $this->draft();
        $proposal->update(['payload' => ['bag-1' => ['coordinates' => '0.50 LS, 130.50 BT']]]);
        $this->upload($proposal)->assertSee('Global Mangrove Watch')->assertSee('Analisis dengan AI');
        $image = $proposal->attachments()->firstOrFail();

        $this->assertSame('mangrove', $image->anchor_key);
        $this->assertSame('inline', $image->placement);
        $this->assertSame('gmw', $image->source_metadata['source']);
        $this->assertSame('2025', $image->source_metadata['period']);
        $this->assertSame('0.50 LS, 130.50 BT', $image->source_metadata['coordinates']);
        $this->assertStringContainsString('Global Mangrove Watch', $image->caption);
        Storage::disk('kkprl_private')->assertExists($image->storage_path);
        $this->assertArrayNotHasKey('bag-3', $proposal->fresh()->payload);
    }

    #[Test]
    public function image_analysis_updates_only_selected_preview_and_persists_until_confirmation(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        $binary = Storage::disk('kkprl_private')->get($image->storage_path);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '```json {"mangrove":"Pada peta terlihat sebaran mangrove di sekitar lokasi."} ```']]]]]])]);

        $component->set('previewData.seagrass', 'Koreksi lamun tetap tersedia.')
            ->call('analyzeEvidence', $image->id)
            ->assertHasNoErrors()
            ->assertSet('previewData.mangrove', fn ($value) => str_starts_with($value, 'Pada peta terlihat sebaran mangrove di sekitar lokasi.') && str_contains($value, 'Sumber: Global Mangrove Watch'))
            ->assertSet('previewData.seagrass', 'Koreksi lamun tetap tersedia.')
            ->assertSee('Silakan tinjau')
            ->assertDontSee('```');
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'synthetic-test-key')
            && $request['contents'][0]['parts'][1]['inline_data']['data'] === base64_encode($binary)
            && str_contains($request['system_instruction']['parts'][0]['text'], 'Jangan mengarang angka')
            && str_contains($request['system_instruction']['parts'][0]['text'], '2025'));
        $this->assertEmpty($proposal->fresh()->payload);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->assertSet('previewData.mangrove', fn ($value) => str_starts_with($value, 'Pada peta terlihat sebaran mangrove di sekitar lokasi.') && str_contains($value, 'Sumber: Global Mangrove Watch'))
            ->call('confirmAndSave');
        $this->assertStringContainsString('Pada peta terlihat sebaran mangrove di sekitar lokasi.', $proposal->fresh()->payload['bag-3']['mangrove']);
    }

    #[Test]
    public function unreadable_images_can_request_clarification_without_overwriting_the_preview(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        $this->mock(KkprlAiService::class, fn ($mock) => $mock->shouldReceive('analyzeImage')->once()->andReturn('Legenda belum terbaca. Bisa unggah gambar yang lebih jelas?'));
        $component->set('previewData.mangrove', 'Narasi manual.')
            ->call('analyzeEvidence', $image->id)
            ->assertSet('previewData.mangrove', 'Narasi manual.')
            ->assertSee('Legenda belum terbaca.');
    }

    #[Test]
    public function patches_for_other_fields_are_rejected_atomically(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        $this->mock(KkprlAiService::class, fn ($mock) => $mock->shouldReceive('analyzeImage')->once()->andReturn('```json {"mangrove":"Pengganti", "seagrass":"Tidak diminta"} ```'));
        $component->set('previewData.mangrove', 'Narasi manual.')
            ->call('analyzeEvidence', $image->id)
            ->assertSet('previewData.mangrove', 'Narasi manual.')
            ->assertSee('Narasi sebelumnya tetap tersedia')
            ->assertDontSee('Tidak diminta');
    }

    #[Test]
    public function failed_ai_keeps_the_image_and_manual_draft_available_for_retry(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        $this->mock(KkprlAiService::class, fn ($mock) => $mock->shouldReceive('analyzeImage')->once()->andReturn(null));
        $component->set('previewData.mangrove', 'Narasi manual.')
            ->call('analyzeEvidence', $image->id)
            ->assertSet('previewData.mangrove', 'Narasi manual.')
            ->assertSee('Coba analisis lagi');
        Storage::disk('kkprl_private')->assertExists($image->storage_path);
    }

    #[Test]
    public function non_image_uploads_and_unsafe_source_urls_are_rejected(): void
    {
        $proposal = $this->draft();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('evidenceFile', UploadedFile::fake()->create('dokumen.pdf', 20, 'application/pdf'))
            ->call('uploadEvidence')->assertHasErrors('evidenceFile');
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('evidenceUrl', 'javascript:alert(1)')
            ->set('evidenceFile', UploadedFile::fake()->image('map.png'))
            ->call('uploadEvidence')->assertHasErrors('evidenceUrl');
        $this->assertSame(0, $proposal->attachments()->count());
    }

    #[Test]
    public function images_cannot_be_analyzed_from_another_proposal_or_another_chapter(): void
    {
        $other = $this->draft();
        $this->upload($other);
        $image = $other->attachments()->firstOrFail();
        $proposal = $this->draft();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->call('analyzeEvidence', $image->id)->assertStatus(404);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->call('uploadEvidence')->assertStatus(403);
        Http::assertNothingSent();
    }

    #[Test]
    public function deletion_removes_the_image_and_invalidates_its_analysis_access(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        $component->call('deleteEvidence', $image->id)->assertHasNoErrors();
        Storage::disk('kkprl_private')->assertMissing($image->storage_path);
        $this->assertSame(0, $proposal->attachments()->count());
        $component->call('analyzeEvidence', $image->id)->assertStatus(404);
        Http::assertNothingSent();
    }

    #[Test]
    public function changed_coordinates_require_a_new_location_image(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        $proposal->update(['payload' => ['bag-1' => ['coordinates' => 'Lokasi baru']]]);
        $component->call('analyzeEvidence', $image->id)->assertSee('Koordinat Bab 1 berubah');
        Http::assertNothingSent();
    }

    #[Test]
    public function submission_during_analysis_prevents_the_new_preview_from_being_saved(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        $component->set('previewData.mangrove', 'Koreksi sebelum analisis.');
        $this->mock(KkprlAiService::class, function ($mock) use ($proposal): void {
            $mock->shouldReceive('analyzeImage')->once()->andReturnUsing(function () use ($proposal): string {
                $proposal->update(['status' => 'submitted']);

                return '```json {"mangrove":"Tidak boleh tersimpan"} ```';
            });
        });
        $component->call('analyzeEvidence', $image->id)->assertStatus(409);
        $chat = $proposal->chats()->where('chapter', 'bag-3')->firstOrFail();
        $checkpoint = collect($chat->messages)->firstWhere('role', 'system_preview');
        $this->assertSame('Koreksi sebelum analisis.', $checkpoint['data']['mangrove']);
    }

    #[Test]
    public function chapter_quota_and_expired_access_are_enforced_for_evidence_actions(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        for ($index = 0; $index < 9; $index++) {
            app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-3', UploadedFile::fake()->image('extra.png'));
        }
        $component->set('evidenceFile', UploadedFile::fake()->image('over-quota.png'))
            ->call('uploadEvidence')->assertHasErrors('evidenceFile')->assertSee('10 file');
        $this->assertSame(10, $proposal->attachments()->count());
        app(KkprlProposalAccessService::class)->forget();
        $component->call('deleteEvidence', $image->id)->assertStatus(403);
        Storage::disk('kkprl_private')->assertExists($image->storage_path);
    }

    #[Test]
    public function corrupt_files_and_generic_replacements_cannot_reuse_source_provenance(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        Storage::disk('kkprl_private')->put($image->storage_path, 'corrupt');
        $component->call('analyzeEvidence', $image->id)->assertSee('File gambar berubah');
        Http::assertNothingSent();
        $replacement = app(KkprlProposalAttachmentService::class)->replace($proposal, $image, UploadedFile::fake()->image('different.png'));
        $this->assertNull($replacement->source_metadata);
        $this->assertNull($replacement->caption);
        $this->assertSame('supporting', $replacement->attachment_role);
        $component->call('analyzeEvidence', $image->id)->assertStatus(404);
    }

    #[Test]
    public function concurrent_manual_corrections_are_not_overwritten_by_image_analysis(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        $component->set('previewData.mangrove', 'Narasi awal.');
        $this->mock(KkprlAiService::class, function ($mock) use ($proposal): void {
            $mock->shouldReceive('analyzeImage')->once()->andReturnUsing(function () use ($proposal): string {
                Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
                    ->set('previewData.seagrass', 'Koreksi dari tab lain.');

                return '```json {"mangrove":"Narasi pengganti"} ```';
            });
        });
        $component->call('analyzeEvidence', $image->id)->assertSee('Pratinjau berubah');
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->assertSet('previewData.mangrove', 'Narasi awal.')
            ->assertSet('previewData.seagrass', 'Koreksi dari tab lain.');
    }

    #[Test]
    public function source_metadata_survives_snapshot_and_changes_invalidate_the_manifest(): void
    {
        $proposal = $this->draft();
        $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        $payload = [];
        foreach (ProposalProgress::requiredFields('bag-3') as $field) {
            $payload['bag-3'][$field] = KkprlPayloadFixture::value($field);
        }
        $proposal->update(['payload' => $payload]);
        $snapshots = app(KkprlProposalSnapshotService::class);
        $first = $snapshots->capture($proposal->fresh(), 'bag-3');
        $this->assertSame($image->source_metadata, $first->attachments[0]['source_metadata']);
        $image->update(['source_metadata' => array_replace($image->source_metadata, ['period' => '2024'])]);
        $second = $snapshots->capture($proposal->fresh(), 'bag-3');
        $this->assertNotSame($first->attachmentManifestHash, $second->attachmentManifestHash);
    }

    #[Test]
    public function daily_quota_errors_explain_the_wait_and_preserve_image_and_manual_preview(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $image = $proposal->attachments()->firstOrFail();
        Http::fake(['*' => Http::response(KkprlAiRateLimitTest::quota('GenerateRequestsPerDayPerProjectPerModel-FreeTier', '85236s'), 429)]);
        $component->set('previewData.mangrove', 'Koreksi mangrove pengguna.')
            ->call('analyzeEvidence', $image->id)
            ->assertSee('Kuota harian')
            ->assertSee('23 jam')
            ->assertSet('previewData.mangrove', 'Koreksi mangrove pengguna.')
            ->call('analyzeEvidence', $image->id)
            ->assertSee('Kuota harian');
        Http::assertSentCount(1);
        $this->assertEmpty($proposal->fresh()->payload);
        Storage::disk('kkprl_private')->assertExists($image->storage_path);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->assertSet('previewData.mangrove', 'Koreksi mangrove pengguna.');
    }

    #[Test]
    public function quota_failure_keeps_the_typed_chat_answer_available(): void
    {
        $proposal = $this->draft();
        Http::fake(['*' => Http::response(KkprlAiRateLimitTest::quota('GenerateRequestsPerDayPerProjectPerModel-FreeTier', '85236s'), 429)]);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('userInput', 'Di sekitar lokasi ada hutan bakau.')
            ->call('sendMessage')
            ->assertSee('Kuota harian')
            ->assertSet('userInput', 'Di sekitar lokasi ada hutan bakau.');
        $this->assertSame(0, $proposal->chats()->count());
    }
}
