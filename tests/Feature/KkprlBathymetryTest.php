<?php

namespace Tests\Feature;

use App\Domain\Kkprl\InvalidBathymetry;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlAiChat;
use App\Models\KkprlProposal;
use App\Services\KkprlAiService;
use App\Services\KkprlBathymetryMapRenderer;
use App\Services\KkprlBathymetryService;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Smalot\PdfParser\Parser;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlBathymetryTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kkprl_private');
        Http::preventStrayRequests();
    }

    public static function grid(): string
    {
        // First row north, first column west. Bottom-left coordinate describes cell edges.
        return "ncols 4\nnrows 4\nxllcorner 130.87\nyllcorner -0.49\ncellsize 0.005\nnodata_value -2147483648\n"
            ."-10 -20 -30 -40\n-50 -60 -70 -80\n-90 -100 -110 -120\n-130 -140 -2147483648 5\n";
    }

    private function draft(): KkprlProposal
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $proposal->update(['payload' => ['bag-1' => ['coordinates' => 'Titik 1: Latitude 0.48 S (-0.48), Longitude 130.88 E (130.88)',
            'coordinates_raw' => json_encode([['lat_dd' => '-0.48', 'lng_dd' => '130.88']]), 'shape_type' => 'point']]]);

        return $proposal;
    }

    private function builder(KkprlProposal $proposal)
    {
        return Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('evidenceField', 'bathymetry');
    }

    #[Test]
    public function selecting_bathymetry_defaults_to_gmrt_and_offers_a_native_map_builder(): void
    {
        $this->builder($this->draft())->assertSet('evidenceSource', 'gmrt')
            ->assertSee('Buat peta batimetri & narasi awal')->assertSee('Garmin Interactive Map');
        Http::assertNothingSent();
    }

    #[Test]
    public function grid_statistics_and_orientation_preserve_nodata_and_land_values(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => Http::response(self::grid())]);
        $result = app(KkprlBathymetryService::class)->fetch([[-0.48, 130.88]], 1);
        $this->assertSame(-10.0, $result['grid']['values'][0][0]);
        $this->assertNull($result['grid']['values'][3][2]);
        $this->assertSame(5.0, $result['grid']['values'][3][3]);
        $summary = $result['summary'];
        $this->assertSame(15, $summary['valid_cells']);
        $this->assertSame(1, $summary['missing_cells']);
        $this->assertSame(1, $summary['nonnegative_cells']);
        $this->assertSame(-140.0, $summary['minimum_elevation_m']);
        $this->assertSame(5.0, $summary['maximum_elevation_m']);
        $this->assertSame(self::grid(), gzdecode(base64_decode($result['archive'])));
        $this->assertSame(hash('sha256', self::grid()), $summary['response_sha256']);
        $this->assertStringContainsString('seluruh area peta', app(KkprlBathymetryService::class)->report($summary));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://www.gmrt.org/services/GridServer?')
            && $request['format'] === 'esriascii' && $request['layer'] === 'topo' && $request['mresolution'] >= 100);
    }

    #[Test]
    public function native_map_is_private_exportable_and_updates_only_the_reviewable_bathymetry_preview(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => Http::response(self::grid())]);
        $proposal = $this->draft();
        $payload = $proposal->payload;
        $component = $this->builder($proposal)->set('previewData.seabed_profile', 'Koreksi penampang.')
            ->call('generateBathymetry')->assertHasNoErrors()
            ->assertSet('previewData.seabed_profile', 'Koreksi penampang.')
            ->assertSet('previewData.bathymetry', fn ($text) => str_contains($text, '-140,0 m') && str_contains($text, 'CC BY 4.0'));
        $attachment = $proposal->attachments()->firstOrFail();
        $this->assertSame('bathymetry', $attachment->anchor_key);
        $this->assertSame('ai_evidence', $attachment->attachment_role);
        $this->assertSame('gmrt', $attachment->source_metadata['source']);
        $this->assertSame(self::grid(), gzdecode(base64_decode($attachment->source_metadata['bathymetry_grid_gzip'])));
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
        $this->get(route('kkprl.proposal.attachment.download', $attachment))->assertOk();
        $this->assertSame($payload, $proposal->fresh()->payload);
        Http::assertSentCount(1);
        $component->call('confirmAndSave')->assertHasNoErrors();
        $this->assertStringContainsString('seluruh area peta', $proposal->fresh()->payload['bag-3']['bathymetry']);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->assertSet('previewData.bathymetry', $proposal->fresh()->payload['bag-3']['bathymetry']);
    }

    #[Test]
    public function missing_or_invalid_location_does_not_fetch_a_guessed_map(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $this->builder($proposal)->call('generateBathymetry')->assertHasErrors('bathymetryArea')->assertSee('Bab 1');
        $this->builder($this->draft())->set('bathymetryBufferKm', '100')->call('generateBathymetry')->assertHasErrors('bathymetryBufferKm');
        Http::assertNothingSent();
    }

    #[Test]
    public function source_timeout_preserves_existing_map_and_manual_narrative(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => Http::response(self::grid())]);
        $proposal = $this->draft();
        $component = $this->builder($proposal)->call('generateBathymetry');
        $attachment = $proposal->attachments()->firstOrFail();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => fn () => throw new ConnectionException]);
        $component->set('previewData.bathymetry', 'Koreksi batimetri.')
            ->call('generateBathymetry')->assertHasErrors('bathymetryArea')->assertSet('previewData.bathymetry', 'Koreksi batimetri.');
        $this->assertSame(1, $proposal->attachments()->count());
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
    }

    #[Test]
    public function malformed_shifted_or_excessive_grids_are_rejected_instead_of_generating_statistics(): void
    {
        $bad = [str_replace('130.87', '129.87', self::grid()), str_replace('ncols 4', 'ncols 1000', self::grid()),
            str_replace('-50 -60', '-50 NaN', self::grid()), str_replace('-130 -140', '-130', self::grid()),
            str_replace('-50 -60', '-50 -99999', self::grid()), self::grid()."1 2 3 4\n", str_repeat('x', 4194305)];
        foreach ($bad as $body) {
            Http::swap(new Factory);
            Http::preventStrayRequests();
            Http::fake(['www.gmrt.org/*' => Http::response($body)]);
            try {
                app(KkprlBathymetryService::class)->fetch([[-0.48, 130.88]], 1);
                $this->fail('Invalid grid accepted.');
            } catch (InvalidBathymetry $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    #[Test]
    public function coordinates_shape_checkpoint_access_and_lock_are_rechecked_after_the_external_request(): void
    {
        $proposal = $this->draft();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => function () use ($proposal) {
            $payload = $proposal->payload;
            $payload['bag-1']['shape_type'] = 'line';
            $proposal->update(['payload' => $payload]);

            return Http::response(self::grid());
        }]);
        $this->builder($proposal)->call('generateBathymetry')->assertSee('Koordinat atau bentuk lokasi Bab 1 berubah');
        $this->assertSame(0, $proposal->attachments()->count());
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => function () use ($proposal) {
            Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
                ->set('previewData.bathymetry', 'Koreksi tab lain.');

            return Http::response(self::grid());
        }]);
        $this->builder($proposal)->call('generateBathymetry')->assertSee('Pratinjau berubah')
            ->assertSet('previewData.bathymetry', 'Koreksi tab lain.');
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => function () use ($proposal) {
            $proposal->update(['status' => 'submitted']);

            return Http::response(self::grid());
        }]);
        $this->builder($proposal)->call('generateBathymetry')->assertStatus(409);
        $this->assertSame(0, $proposal->attachments()->count());
    }

    #[Test]
    public function chapter_scope_access_and_shared_attachment_quota_are_enforced(): void
    {
        $proposal = $this->draft();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-2'])
            ->call('generateBathymetry')->assertStatus(403);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => Http::response(self::grid())]);
        for ($i = 0; $i < 10; $i++) {
            app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-3', UploadedFile::fake()->image('extra.png'));
        }
        $component = $this->builder($proposal)->set('previewData.bathymetry', 'Koreksi batimetri.')
            ->call('generateBathymetry')->assertHasErrors('bathymetryArea')->assertSet('previewData.bathymetry', 'Koreksi batimetri.');
        app(KkprlProposalAccessService::class)->forget();
        $component->call('generateBathymetry')->assertStatus(403);
        $this->assertSame(10, $proposal->attachments()->count());
    }

    #[Test]
    public function ai_receives_only_calculated_summary_and_keeps_numeric_report_after_narration(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => Http::response(self::grid())]);
        $proposal = $this->draft();
        $component = $this->builder($proposal)->call('generateBathymetry');
        $attachment = $proposal->attachments()->firstOrFail();
        $this->mock(KkprlAiService::class, function ($mock): void {
            $mock->shouldReceive('analyzeImage')->twice()->withArgs(fn ($image, $mime, $prompt) => str_contains($prompt, 'minimum_elevation_m')
                && ! str_contains($prompt, 'bathymetry_grid_gzip') && strlen($prompt) < 18000)
                ->andReturn(null, '```json {"bathymetry":"Peta sekunder menunjukkan variasi elevasi pada area sekitar lokasi."} ```');
        });
        $component->set('previewData.bathymetry', 'Koreksi batimetri.')
            ->call('analyzeEvidence', $attachment->id)->assertSet('previewData.bathymetry', 'Koreksi batimetri.')
            ->call('analyzeEvidence', $attachment->id)
            ->assertSet('previewData.bathymetry', fn ($text) => str_contains($text, 'Peta sekunder menunjukkan') && str_contains($text, '-140,0 m') && str_contains($text, 'CC BY 4.0'));
    }

    #[Test]
    public function snapshot_and_word_pdf_export_preserve_map_grid_provenance_and_license(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['www.gmrt.org/*' => Http::response(self::grid())]);
        $proposal = $this->draft();
        $this->builder($proposal)->call('generateBathymetry')->call('confirmAndSave');
        $payload = $proposal->fresh()->payload;
        foreach (ProposalProgress::requiredFields('bag-3') as $field) {
            if ($field !== 'bathymetry') {
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
            if ($document->format === 'pdf') {
                $text = (new Parser)->parseContent(Storage::disk('kkprl_private')->get($document->storage_path))->getText();
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
    }

    #[Test]
    public function incomplete_coordinates_and_unbounded_geographic_areas_are_rejected_before_http(): void
    {
        $proposal = $this->draft();
        $payload = $proposal->payload;
        $payload['bag-1']['coordinates_raw'] = json_encode([['lat_dd' => '-0.48', 'lng_dd' => '130.88'], ['lat_dd' => '', 'lng_dd' => '130.89']]);
        $proposal->update(['payload' => $payload]);
        $this->builder($proposal)->call('generateBathymetry')->assertHasErrors('bathymetryArea');
        foreach ([[[0, 179.999]], [[0, 179], [0, -179]], [[0, 130], [1, 131]], [[85, 130]], [[INF, 130]]] as $points) {
            try {
                app(KkprlBathymetryService::class)->fetch($points, 1);
                $this->fail('Unbounded area accepted.');
            } catch (InvalidBathymetry $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
        Http::assertNothingSent();
    }

    #[Test]
    public function flat_land_and_all_missing_grids_have_explicit_meaning_and_safe_rendering(): void
    {
        foreach ([-25, 5, -2147483648] as $value) {
            $body = implode("\n", array_slice(explode("\n", self::grid()), 0, 6))."\n";
            $body .= str_repeat(implode(' ', array_fill(0, 4, $value))."\n", 4);
            Http::swap(new Factory);
            Http::preventStrayRequests();
            Http::fake(['www.gmrt.org/*' => Http::response($body)]);
            try {
                $result = app(KkprlBathymetryService::class)->fetch([[-0.48, 130.88]], 1);
                $this->assertNotSame(-2147483648, $value);
                $this->assertSame((float) $value, $result['summary']['minimum_elevation_m']);
                $this->assertSame($value >= 0 ? 16 : 0, $result['summary']['nonnegative_cells']);
                $png = app(KkprlBathymetryMapRenderer::class)->png($result, [[-0.48, 130.88]], 'point');
                $this->assertSame([1100, 900], array_slice(getimagesizefromstring($png), 0, 2));
            } catch (InvalidBathymetry $exception) {
                $this->assertSame(-2147483648, $value);
                $this->assertStringContainsString('tidak memiliki nilai grid', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function contours_interpolate_known_plane_crossings_and_never_bridge_missing_data(): void
    {
        $renderer = app(KkprlBathymetryMapRenderer::class);
        // z = -10*x: the -5 m contour must lie on x=0.5, independent of y.
        $segment = $renderer->contourSegment([[0, 0, 0], [1, 0, -10], [1, 1, -10]], -5);
        $this->assertSame([[0.5, 0.0], [0.5, 0.5]], $segment);
        $this->assertNull($renderer->contourSegment([[0, 0, 0], [1, 0, null], [1, 1, -10]], -5));
        $this->assertNull($renderer->contourSegment([[0, 0, -5], [1, 0, -5], [1, 1, -5]], -5));
    }

    #[Test]
    public function map_orientation_missing_cells_and_markers_follow_geographic_positions(): void
    {
        Http::fake(['www.gmrt.org/*' => Http::response(self::grid())]);
        $result = app(KkprlBathymetryService::class)->fetch([[-0.48, 130.88]], 1);
        $png = app(KkprlBathymetryMapRenderer::class)->png($result, [[-0.48, 130.88]], 'point');
        $image = imagecreatefromstring($png);
        // Map is about 600 by 600, with the south-east missing cell centred near (550,640).
        $northWest = imagecolorsforindex($image, imagecolorat($image, 325, 180));
        $southWest = imagecolorsforindex($image, imagecolorat($image, 325, 640));
        $missing = imagecolorsforindex($image, imagecolorat($image, 550, 640));
        $land = imagecolorsforindex($image, imagecolorat($image, 750, 640));
        $marker = imagecolorsforindex($image, imagecolorat($image, 475, 415));
        $this->assertGreaterThan($southWest['blue'], $northWest['blue']);
        $this->assertSame([255, 255, 255], array_values(array_slice($missing, 0, 3)));
        $this->assertGreaterThan($land['red'], $land['green']);
        $this->assertGreaterThan($marker['green'], $marker['red']);
        imagedestroy($image);
    }

    #[Test]
    public function raw_location_and_shape_changes_invalidate_ai_before_and_during_narration(): void
    {
        Http::fake(['www.gmrt.org/*' => Http::response(self::grid())]);
        $proposal = $this->draft();
        $component = $this->builder($proposal)->call('generateBathymetry');
        $attachment = $proposal->attachments()->firstOrFail();
        $this->mock(KkprlAiService::class, function ($mock) use ($proposal): void {
            $mock->shouldReceive('analyzeImage')->once()->andReturnUsing(function () use ($proposal) {
                $payload = $proposal->fresh()->payload;
                $payload['bag-1']['shape_type'] = 'line';
                $proposal->update(['payload' => $payload]);

                return '{"bathymetry":"Narasi lama yang tidak boleh diterima."}';
            });
        });
        $component->set('previewData.bathymetry', 'Koreksi saya.')->call('analyzeEvidence', $attachment->id)
            ->assertSet('previewData.bathymetry', 'Koreksi saya.')->assertSee('Gambar atau koordinat berubah');
        $component->call('analyzeEvidence', $attachment->id)->assertSee('Koordinat atau bentuk lokasi Bab 1 berubah');
        Http::assertSentCount(1);
    }
}
