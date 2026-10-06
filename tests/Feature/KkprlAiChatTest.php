<?php

namespace Tests\Feature;

use App\Livewire\KkprlAiChat;
use App\Livewire\KkprlProposalWizard;
use App\Services\KkprlAiService;
use App\Services\KkprlProposalAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KkprlAiChatTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('draftResponseFormats')]
    public function draft_responses_show_review_instructions_instead_of_internal_data(string $response): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $this->mock(KkprlAiService::class, function ($mock) use ($response): void {
            $mock->shouldReceive('chat')->once()->andReturn($response);
        });

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('userInput', 'Sudah cukup, susun draftnya.')
            ->call('sendMessage')
            ->assertSet('previewData.marine_space_use_narrative', 'Narasi siap ditinjau.')
            ->assertSet('messages.2.content', 'Draft Bab 2 sudah saya susun di bagian Pratinjau Draft. Silakan baca dan periksa apakah isinya sesuai dengan kondisi usaha Anda. Jika ada yang perlu diperbaiki, beri tahu saya atau edit langsung narasinya. Jika sudah sesuai, klik Konfirmasi & Simpan Draft.')
            ->assertDontSee('```')
            ->assertHasNoErrors();

        $this->assertEmpty($proposal->fresh()->payload);
    }

    public static function draftResponseFormats(): array
    {
        $json = '{"marine_space_use_narrative":"Narasi siap ditinjau."}';

        return [
            'fenced JSON only' => ["```json\n{$json}\n```"],
            'uppercase language marker' => ["```JSON\n{$json}\n```"],
            'missing closing code fence' => ["```json\n{$json}"],
            'bare JSON' => [$json],
            'unlabelled fence' => ["```\n{$json}\n```"],
            'technical introduction' => ["Berikut JSON final untuk marine_space_use_narrative:\n```json\n{$json}\n```"],
        ];
    }

    #[Test]
    public function truncated_draft_is_hidden_and_keeps_the_previous_preview(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $this->mock(KkprlAiService::class, function ($mock): void {
            $mock->shouldReceive('chat')->once()->andReturn("```json\n{\"marine_space_use_narrative\":\"Narasi yang terpotong");
        });

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('previewData.marine_space_use_narrative', 'Koreksi pengguna dipertahankan.')
            ->set('userInput', 'Susun draftnya.')
            ->call('sendMessage')
            ->assertSet('previewData.marine_space_use_narrative', 'Koreksi pengguna dipertahankan.')
            ->assertDontSee('```')
            ->assertDontSee('Narasi yang terpotong')
            ->assertSee('Saya belum berhasil menampilkan draft dengan lengkap.');

        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function resuming_a_chat_hides_internal_data_in_older_ai_messages(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $proposal->chats()->create(['chapter' => 'bag-2', 'messages' => [
            ['role' => 'ai', 'content' => "```json\n{\"marine_space_use_narrative\":\"Narasi terpotong"],
            ['role' => 'system_preview', 'data' => ['marine_space_use_narrative' => 'Koreksi terakhir.']],
        ]]);

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->assertDontSee('```')
            ->assertDontSee('Narasi terpotong')
            ->assertSet('previewData.marine_space_use_narrative', 'Koreksi terakhir.');

        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function follow_up_questions_remain_visible_while_a_partial_draft_is_updated(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $this->mock(KkprlAiService::class, function ($mock): void {
            $mock->shouldReceive('chat')->once()->andReturn('Bagaimana kondisi lamun di lokasi? ```json {"mangrove":"Mangrove ditemukan di pesisir."} ```');
        });

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('userInput', 'Ada mangrove di pesisir.')
            ->call('sendMessage')
            ->assertSet('messages.2.content', 'Bagaimana kondisi lamun di lokasi?')
            ->assertSet('previewData.mangrove', 'Mangrove ditemukan di pesisir.')
            ->assertDontSee('```');
    }

    #[Test]
    public function an_ai_patch_outside_the_chapter_is_hidden_and_does_not_replace_manual_edits(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $this->mock(KkprlAiService::class, function ($mock): void {
            $mock->shouldReceive('chat')->once()->andReturn('```json {"marine_space_use_narrative":"Pengganti", "identity_number":"Tidak diizinkan"} ```');
        });

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('previewData.marine_space_use_narrative', 'Koreksi manual.')
            ->set('userInput', 'Susun draftnya.')
            ->call('sendMessage')
            ->assertSet('previewData.marine_space_use_narrative', 'Koreksi manual.')
            ->assertDontSee('```')
            ->assertDontSee('Tidak diizinkan')
            ->assertSee('Saya belum berhasil menampilkan draft dengan lengkap.');
        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function manual_correction_stays_in_preview_until_confirmed_and_survives_resume(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $proposal->update(['payload' => ['bag-2' => ['marine_space_use_narrative' => 'Narasi awal.']]]);

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->assertSee('Narasi penggunaan ruang laut')
            ->assertSee('Konfirmasi & Simpan Draft')
            ->set('previewData.marine_space_use_narrative', 'Lokasi digunakan untuk penangkapan ikan oleh nelayan setempat.');

        $this->assertSame('Narasi awal.', $proposal->fresh()->payload['bag-2']['marine_space_use_narrative']);

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->assertSet('previewData.marine_space_use_narrative', 'Lokasi digunakan untuk penangkapan ikan oleh nelayan setempat.')
            ->call('confirmAndSave')
            ->assertDispatched('draft-saved');

        $saved = $proposal->fresh();
        $this->assertSame('Lokasi digunakan untuk penangkapan ikan oleh nelayan setempat.', $saved->payload['bag-2']['marine_space_use_narrative']);
        $this->assertNotNull($saved->last_saved_at);
        $this->assertGreaterThan(0, $saved->progress_percent);
    }

    #[Test]
    public function chat_revision_uses_manual_preview_and_does_not_save_proposal_without_confirmation(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $this->mock(KkprlAiService::class, function ($mock): void {
            $mock->shouldReceive('chat')->once()->withArgs(function ($messages, $prompt): bool {
                return str_contains($prompt, 'Koreksi manual nelayan setempat.')
                    && str_contains($prompt, 'marine_space_use_narrative')
                    && end($messages)['content'] === 'Perbaiki susunan kalimatnya.';
            })->andReturn("Berikut revisinya.\n```json\n{\"marine_space_use_narrative\":\"Perairan dimanfaatkan oleh nelayan setempat untuk penangkapan ikan.\"}\n```");
        });

        $component = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('previewData.marine_space_use_narrative', 'Koreksi manual nelayan setempat.')
            ->set('userInput', 'Perbaiki susunan kalimatnya.')
            ->call('sendMessage')
            ->assertSet('previewData.marine_space_use_narrative', 'Perairan dimanfaatkan oleh nelayan setempat untuk penangkapan ikan.');

        $this->assertEmpty($proposal->fresh()->payload);
        $component->call('confirmAndSave');
        $this->assertSame('Perairan dimanfaatkan oleh nelayan setempat untuk penangkapan ikan.', $proposal->fresh()->payload['bag-2']['marine_space_use_narrative']);
    }

    #[Test]
    public function partial_draft_preserves_other_chapters_and_latest_manual_correction(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $proposal->update(['payload' => ['bag-1' => ['province' => 'Papua Barat'], 'bag-2' => ['marine_space_use_narrative' => 'Tetap ada.']]]);

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('previewData.mangrove', 'Mangrove ditemukan di pesisir.')
            ->call('confirmAndSave');

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('previewData.mangrove', 'Mangrove belum terverifikasi.')
            ->call('confirmAndSave');

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->assertSet('previewData.mangrove', 'Mangrove belum terverifikasi.');

        $this->assertSame('Papua Barat', $proposal->fresh()->payload['bag-1']['province']);
        $this->assertSame('Tetap ada.', $proposal->fresh()->payload['bag-2']['marine_space_use_narrative']);
        $this->assertSame('draft', $proposal->fresh()->status);
    }

    #[Test]
    public function reclamation_schedule_can_be_edited_as_rows(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $proposal->update(['payload' => ['includes_reclamation' => true]]);

        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-4'])
            ->call('addScheduleRow')
            ->set('previewData.reclamation_schedule_rows.0.activity', 'Persiapan')
            ->set('previewData.reclamation_schedule_rows.0.start_date', '2026-11-01')
            ->set('previewData.reclamation_schedule_rows.0.end_date', '2026-11-30')
            ->set('previewData.reclamation_schedule_rows.0.notes', 'Rencana awal')
            ->call('addScheduleRow')
            ->call('removeScheduleRow', 1)
            ->call('confirmAndSave');

        $this->assertSame([['activity' => 'Persiapan', 'start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'notes' => 'Rencana awal']], $proposal->fresh()->payload['bag-4']['reclamation_schedule_rows']);
    }

    #[Test]
    public function malformed_preview_cannot_be_saved(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('previewData.marine_space_use_narrative', ['unexpected' => 'array'])
            ->assertHasErrors('previewData.marine_space_use_narrative')
            ->call('confirmAndSave')
            ->assertHasErrors('previewData.marine_space_use_narrative');
        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function expired_session_cannot_confirm_preview(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '081234567890']);
        $component = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('previewData.marine_space_use_narrative', 'Belum dikonfirmasi.');
        $access->forget();
        $component->call('confirmAndSave')->assertStatus(403);
        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function chat_cannot_open_another_proposal_or_inactive_chapter(): void
    {
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '081234567890']);
        $other = $access->startDraft(['phone' => '081299999999']);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])->assertStatus(403);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $other->id, 'chapter' => 'bag-4'])->assertStatus(403);
        $this->assertDatabaseCount('kkprl_proposal_chats', 0);
    }

    #[Test]
    public function wizard_refreshes_chapter_progress_after_preview_confirmation(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $wizard = Livewire::test(KkprlProposalWizard::class);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('previewData.marine_space_use_narrative', 'Narasi lengkap.')
            ->call('confirmAndSave');
        $wizard->dispatch('draft-saved')->assertSet('chapterProgress.bag-2.status', 'complete');
    }

    #[Test]
    public function confirming_preview_preserves_fields_from_legacy_flat_drafts(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $raw = '[{"lat_dd":"-6.2","lng_dd":"106.8"}]';
        $proposal->update(['payload' => ['province' => 'Papua Barat', 'coordinates_raw' => $raw, 'mangrove' => 'Data ekosistem tersimpan.']]);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('previewData.marine_space_use_narrative', 'Narasi baru.')
            ->call('confirmAndSave');
        $this->assertSame('Papua Barat', $proposal->fresh()->payload['bag-1']['province']);
        $this->assertSame($raw, $proposal->fresh()->payload['bag-1']['coordinates_raw']);
        $this->assertSame('Data ekosistem tersimpan.', $proposal->fresh()->payload['bag-3']['mangrove']);
    }

    #[Test]
    public function locked_proposal_rejects_confirmation_without_changing_chat(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $component = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('previewData.marine_space_use_narrative', 'Belum dikonfirmasi.');
        $chatBefore = $proposal->chats()->first()->messages;
        $proposal->update(['status' => 'submitted']);
        $component->call('confirmAndSave')->assertStatus(409);
        $this->assertEmpty($proposal->fresh()->payload);
        $this->assertSame($chatBefore, $proposal->chats()->first()->messages);
    }

    #[Test]
    public function invalid_ai_json_preserves_manual_correction(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $this->mock(KkprlAiService::class, function ($mock): void {
            $mock->shouldReceive('chat')->once()->andReturn('```json {"marine_space_use_narrative": ["invalid"]} ```');
        });
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('previewData.marine_space_use_narrative', 'Koreksi yang dipertahankan.')
            ->set('userInput', 'Perbaiki kalimatnya.')
            ->call('sendMessage')
            ->assertSet('previewData.marine_space_use_narrative', 'Koreksi yang dipertahankan.')
            ->assertSee('Saya belum berhasil menampilkan draft dengan lengkap.');
        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function fields_outside_the_active_chapter_cannot_be_confirmed(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('previewData.identity_number', 'Forbidden field')
            ->assertHasErrors('previewData')
            ->call('confirmAndSave')->assertHasErrors('previewData');
        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function failed_ai_request_keeps_manual_editor_usable(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $this->mock(KkprlAiService::class, function ($mock): void {
            $mock->shouldReceive('chat')->once()->andReturnNull();
        });
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->set('userInput', 'Bantu susun narasi.')
            ->call('sendMessage')
            ->assertSet('userInput', 'Bantu susun narasi.')
            ->set('previewData.marine_space_use_narrative', 'Koreksi manual tetap dapat disimpan.')
            ->call('confirmAndSave');
        $this->assertSame('Koreksi manual tetap dapat disimpan.', $proposal->fresh()->payload['bag-2']['marine_space_use_narrative']);
    }
}
