<?php

namespace Tests\Feature;

use App\Domain\Kkprl\InvalidSeabedProfile;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlAiChat;
use App\Models\KkprlProposal;
use App\Services\KkprlAiService;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalSnapshotService;
use App\Services\KkprlSeabedProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Smalot\PdfParser\Parser;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlSeabedProfileTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kkprl_private');
        Http::preventStrayRequests();
    }

    public static function samples(): array
    {
        // Latitude precedes longitude in the actual GMRT JSON endpoint.
        return [[-0.48, 130.88, -20.0, 0.0], [-0.485, 130.885, -35.0, 784.5], [-0.49, 130.89, -25.0, 1569.0]];
    }

    private function draft(): KkprlProposal
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $proposal->update(['payload' => ['bag-1' => ['coordinates' => 'Titik 1: Latitude 0.48 S (-0.48), Longitude 130.88 E (130.88)',
            'coordinates_raw' => json_encode([['lat_dd' => '-0.48', 'lng_dd' => '130.88']])]]]);

        return $proposal;
    }

    private function builder(KkprlProposal $proposal)
    {
        return Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('evidenceField', 'seabed_profile')
            ->set('profileStartLat', '-0.48')->set('profileStartLng', '130.88')
            ->set('profileEndLat', '-0.49')->set('profileEndLng', '130.89');
    }

    #[Test]
    public function gmrt_coordinates_keep_the_actual_latitude_longitude_order_and_signed_elevations(): void
    {
        Http::fake(['www.gmrt.org/*' => Http::response(self::samples())]);
        $profile = app(KkprlSeabedProfileService::class)->fetch(-0.48, 130.88, -0.49, 130.89);
        $this->assertSame(-0.48, $profile['samples'][0]['latitude']);
        $this->assertSame(130.88, $profile['samples'][0]['longitude']);
        $this->assertSame(-35.0, $profile['minimum_elevation_m']);
        $this->assertSame(-20.0, $profile['maximum_elevation_m']);
        $this->assertSame(1569.0, $profile['length_m']);
        $this->assertStringContainsString('bukan resolusi grid', app(KkprlSeabedProfileService::class)->report($profile));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://www.gmrt.org/services/ProfileServer?start_latitude=-0.48&start_longitude=130.88&end_latitude=-0.49&end_longitude=130.89&format=json');
    }

    #[Test]
    public function generating_a_profile_creates_a_private_exportable_graph_and_reviewable_draft_without_ai(): void
    {
        Http::fake(['www.gmrt.org/*' => Http::response(self::samples())]);
        $proposal = $this->draft();
        $payload = $proposal->payload;
        $component = $this->builder($proposal)->set('previewData.mangrove', 'Koreksi mangrove.')
            ->call('generateSeabedProfile')->assertHasNoErrors()
            ->assertSet('previewData.mangrove', 'Koreksi mangrove.')
            ->assertSet('previewData.seabed_profile', fn ($text) => str_contains($text, '1.569,0 m') && str_contains($text, 'CC BY 4.0'))
            ->assertSee('Tinjau Pratinjau Draft');
        $attachment = $proposal->attachments()->firstOrFail();
        $this->assertSame('ai_evidence', $attachment->attachment_role);
        $this->assertSame('seabed_profile', $attachment->anchor_key);
        $this->assertSame('gmrt', $attachment->source_metadata['source']);
        $this->assertSame(3, $attachment->source_metadata['profile']['valid_samples']);
        $this->assertSame('image/png', $attachment->mime_type);
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
        $this->assertSame($payload, $proposal->fresh()->payload);
        Http::assertSentCount(1);
        $this->get(route('kkprl.proposal.attachment.download', $attachment))->assertOk();
        $component->call('confirmAndSave')->assertHasNoErrors();
        $this->assertStringContainsString('GMRT', $proposal->fresh()->payload['bag-3']['seabed_profile']);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->assertSet('previewData.seabed_profile', $proposal->fresh()->payload['bag-3']['seabed_profile']);
    }

    #[Test]
    public function source_failure_keeps_previous_graph_and_manual_text(): void
    {
        Http::fake(['www.gmrt.org/*' => Http::response(self::samples())]);
        $proposal = $this->draft();
        $component = $this->builder($proposal)->call('generateSeabedProfile');
        $attachment = $proposal->attachments()->firstOrFail();
        Http::fake(['www.gmrt.org/*' => fn () => throw new ConnectionException]);
        $component->set('previewData.seabed_profile', 'Koreksi profil.')
            ->call('generateSeabedProfile')->assertHasErrors('profileTransect')
            ->assertSet('previewData.seabed_profile', 'Koreksi profil.');
        $this->assertSame(1, $proposal->attachments()->count());
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
    }

    #[Test]
    public function invalid_or_coincident_points_do_not_call_the_external_service(): void
    {
        $proposal = $this->draft();
        $this->builder($proposal)->set('profileStartLat', 'NaN')->call('generateSeabedProfile')->assertHasErrors('profileStartLat');
        $this->builder($proposal)->set('profileEndLat', '-0.48')->set('profileEndLng', '130.88')
            ->call('generateSeabedProfile')->assertHasErrors('profileTransect');
        Http::assertNothingSent();
    }

    #[Test]
    public function changed_coordinates_during_fetch_do_not_store_a_stale_graph_or_preview(): void
    {
        $proposal = $this->draft();
        Http::fake(['www.gmrt.org/*' => function () use ($proposal) {
            $proposal->update(['payload' => ['bag-1' => ['coordinates' => 'Lokasi berubah']]]);

            return Http::response(self::samples());
        }]);
        $this->builder($proposal)->set('previewData.seabed_profile', 'Koreksi profil.')
            ->call('generateSeabedProfile')->assertSee('Koordinat Bab 1 berubah')
            ->assertSet('previewData.seabed_profile', 'Koreksi profil.');
        $this->assertSame(0, $proposal->attachments()->count());
    }

    #[Test]
    public function generated_profiles_are_available_only_to_editable_chapter_three_with_active_access(): void
    {
        $proposal = $this->draft();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->call('generateSeabedProfile')->assertStatus(403);
        $component = $this->builder($proposal);
        $expiredComponent = $this->builder($proposal);
        $proposal->update(['status' => 'submitted']);
        $component->call('generateSeabedProfile')->assertStatus(409);
        app(KkprlProposalAccessService::class)->forget();
        $expiredComponent->call('generateSeabedProfile')->assertStatus(403);
        Http::assertNothingSent();
    }

    #[Test]
    public function graph_generation_respects_the_shared_attachment_quota_without_replacing_manual_text(): void
    {
        Http::fake(['www.gmrt.org/*' => Http::response(self::samples())]);
        $proposal = $this->draft();
        for ($i = 0; $i < 10; $i++) {
            app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-3', UploadedFile::fake()->image('extra.png'));
        }
        $this->builder($proposal)->set('previewData.seabed_profile', 'Koreksi profil.')
            ->call('generateSeabedProfile')->assertHasErrors('profileTransect')
            ->assertSet('previewData.seabed_profile', 'Koreksi profil.');
        $this->assertSame(10, $proposal->attachments()->count());
    }

    #[Test]
    public function concurrent_corrections_and_submission_during_fetch_are_rechecked(): void
    {
        $proposal = $this->draft();
        Http::fake(['www.gmrt.org/*' => function () use ($proposal) {
            Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
                ->set('previewData.mangrove', 'Koreksi dari tab lain.');

            return Http::response(self::samples());
        }]);
        $this->builder($proposal)->call('generateSeabedProfile')->assertSee('Pratinjau berubah')
            ->assertSet('previewData.mangrove', 'Koreksi dari tab lain.');
        $this->assertSame(0, $proposal->attachments()->count());
        Http::fake(['www.gmrt.org/*' => function () use ($proposal) {
            $proposal->update(['status' => 'submitted']);

            return Http::response(self::samples());
        }]);
        $this->builder($proposal)->call('generateSeabedProfile')->assertStatus(409);
        $this->assertSame(0, $proposal->attachments()->count());
    }

    #[Test]
    public function ai_is_optional_and_its_narrative_retains_the_server_report_license_and_manual_corrections(): void
    {
        Http::fake(['www.gmrt.org/*' => Http::response(self::samples())]);
        $proposal = $this->draft();
        $component = $this->builder($proposal)->call('generateSeabedProfile');
        $attachment = $proposal->attachments()->firstOrFail();
        $this->mock(KkprlAiService::class, fn ($mock) => $mock->shouldReceive('analyzeImage')->twice()
            ->andReturn(null, '```json {"seabed_profile":"Profil sekunder menunjukkan perubahan elevasi sepanjang lintasan."} ```'));
        $component->set('previewData.seabed_profile', 'Koreksi profil saya.')
            ->call('analyzeEvidence', $attachment->id)->assertSet('previewData.seabed_profile', 'Koreksi profil saya.')
            ->assertSee('Coba analisis lagi')
            ->call('analyzeEvidence', $attachment->id)
            ->assertSet('previewData.seabed_profile', fn ($text) => str_contains($text, 'Profil sekunder menunjukkan')
                && str_contains($text, '-35,0 m') && str_contains($text, 'bukan resolusi grid') && str_contains($text, 'CC BY 4.0'));
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
        $this->assertArrayNotHasKey('bag-3', $proposal->fresh()->payload);
    }

    #[Test]
    public function raw_coordinate_changes_invalidate_ai_even_if_the_human_readable_text_is_unchanged(): void
    {
        Http::fake(['www.gmrt.org/*' => Http::response(self::samples())]);
        $proposal = $this->draft();
        $component = $this->builder($proposal)->call('generateSeabedProfile');
        $attachment = $proposal->attachments()->firstOrFail();
        $payload = $proposal->payload;
        $payload['bag-1']['coordinates_raw'] = json_encode([['lat_dd' => '-0.6', 'lng_dd' => '131']]);
        $proposal->update(['payload' => $payload]);
        $component->call('analyzeEvidence', $attachment->id)->assertSee('Koordinat Bab 1 berubah');
        Http::assertSentCount(1);
    }

    #[Test]
    public function graph_and_profile_provenance_survive_snapshot_and_word_pdf_export(): void
    {
        Http::fake(['www.gmrt.org/*' => Http::response(self::samples())]);
        $proposal = $this->draft();
        $component = $this->builder($proposal)->call('generateSeabedProfile')->call('confirmAndSave');
        $payload = $proposal->fresh()->payload;
        foreach (ProposalProgress::requiredFields('bag-3') as $field) {
            if ($field !== 'seabed_profile') {
                $payload['bag-3'][$field] = KkprlPayloadFixture::value($field);
            }
        }
        $proposal->update(['payload' => $payload]);
        $attachment = $proposal->attachments()->firstOrFail();
        $snapshot = app(KkprlProposalSnapshotService::class)->capture($proposal->fresh(), 'bag-3');
        $this->assertSame($attachment->source_metadata, $snapshot->attachments[0]['source_metadata']);
        $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-3');
        foreach ($documents as $document) {
            $this->assertSame('generated', $document->generation_status);
            $binary = Storage::disk('kkprl_private')->get($document->storage_path);
            if ($document->format === 'pdf') {
                $this->assertStringStartsWith('%PDF', $binary);
                $text = (new Parser)->parseContent($binary)->getText();
                $this->assertStringContainsString('CC BY 4.0', $text);
                $this->assertStringContainsString('GMRT', $text);
            } else {
                $zip = new \ZipArchive;
                $zip->open(Storage::disk('kkprl_private')->path($document->storage_path));
                $xml = $zip->getFromName('word/document.xml');
                $this->assertStringContainsString('CC BY 4.0', $xml);
                $this->assertStringContainsString('<w:drawing>', $xml);
                $zip->close();
            }
        }
        $attachment->update(['source_metadata' => array_replace($attachment->source_metadata, ['notes' => 'Revisi keterangan'])]);
        $changed = app(KkprlProposalSnapshotService::class)->capture($proposal->fresh(), 'bag-3');
        $this->assertNotSame($snapshot->attachmentManifestHash, $changed->attachmentManifestHash);
    }

    #[Test]
    public function missing_samples_are_preserved_as_gaps_and_land_is_not_called_depth(): void
    {
        $samples = self::samples();
        $samples[0][2] = 5;
        $samples[1][2] = null;
        Http::fake(['www.gmrt.org/*' => Http::response($samples)]);
        $profile = app(KkprlSeabedProfileService::class)->fetch(-0.48, 130.88, -0.49, 130.89);
        $this->assertNull($profile['samples'][1]['elevation_m']);
        $this->assertSame(1, $profile['missing_samples']);
        $this->assertSame(1, $profile['nonnegative_samples']);
        $report = app(KkprlSeabedProfileService::class)->report($profile);
        $this->assertStringContainsString('daratan', $report);
        $this->assertStringContainsString('tidak diinterpolasi', $report);
        $this->assertStringNotContainsString('kedalaman minimum', $report);
    }

    #[Test]
    public function wrong_endpoint_coordinates_or_nonmonotonic_distances_are_rejected(): void
    {
        foreach (['swapped', 'distance', 'missing', 'large', 'redirect', 'body', 'nonfinite'] as $case) {
            $samples = self::samples();
            if ($case === 'swapped') {
                $samples = array_map(fn ($row) => [$row[1], $row[0], $row[2], $row[3]], $samples);
            }
            if ($case === 'distance') {
                $samples[1][3] = 1800;
            }
            if ($case === 'missing') {
                $samples = array_map(fn ($row) => [$row[0], $row[1], null, $row[3]], $samples);
            }
            if ($case === 'large') {
                $samples = array_fill(0, 1001, self::samples()[0]);
            }
            if ($case === 'nonfinite') {
                $samples[1][2] = 'NaN';
            }
            Http::fake(['www.gmrt.org/*' => Http::response($case === 'body' ? str_repeat('x', 262145) : $samples, $case === 'redirect' ? 302 : 200)]);
            try {
                app(KkprlSeabedProfileService::class)->fetch(-0.48, 130.88, -0.49, 130.89);
                $this->fail('Invalid response accepted: '.$case);
            } catch (InvalidSeabedProfile $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }
}
