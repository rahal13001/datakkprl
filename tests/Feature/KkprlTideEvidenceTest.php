<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlAiChat;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalChat;
use App\Services\KkprlAiService;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalSnapshotService;
use GuzzleHttp\Exception\ConnectTimeoutException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Smalot\PdfParser\Parser;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlTideEvidenceTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kkprl_private');
        Http::preventStrayRequests();
        config(['services.gemini.key' => 'synthetic-test-key']);
    }

    private function draft(): KkprlProposal
    {
        return app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
    }

    private function upload(KkprlProposal $proposal, string $csv = "waktu,tinggi\n2026-10-01 00:00,100\n2026-10-01 01:00,150\n2026-10-01 02:00,50\n")
    {
        return Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('evidenceField', 'tide')
            ->set('tideFile', UploadedFile::fake()->createWithContent('pasut.csv', $csv))
            ->set('tideUnit', 'cm')->set('tideTimezone', 'Asia/Jayapura')
            ->set('tideDatum', 'Nol palem stasiun')
            ->call('uploadTideData')->assertHasNoErrors();
    }

    private function harmonicCsv(): string
    {
        $speeds = [28.9841042, 30, 15.0410686, 13.9430356, 28.4397295, 13.3986609, 57.9682084, 58.9841042];
        $amplitudes = [0.6, 0.2, 0.3, 0.1, 0.09, 0.04, 0.03, 0.02];
        $csv = "waktu,tinggi\n";
        for ($hour = 0; $hour <= 31 * 24; $hour++) {
            $height = 2 + 0.002 * $hour / 24;
            foreach ($speeds as $i => $speed) {
                $height += $amplitudes[$i] * cos(deg2rad($speed * $hour) + 0.37 * $i);
            }
            $csv .= gmdate('Y-m-d H:i:s', 1788220800 + $hour * 3600).','.($height * 100)."\n";
        }

        return $csv;
    }

    #[Test]
    public function chart_is_private_downloadable_and_does_not_replace_manual_narrative(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal)->assertSee('Buat grafik pasut');
        $data = $proposal->attachments()->firstOrFail();
        $component->set('evidenceField', 'waves')->set('previewData.tide', 'Narasi yang sudah saya koreksi.')
            ->call('generateTideChart', $data->id)->assertHasNoErrors()
            ->assertSet('errorMessage', null)->assertSet('previewData.tide', 'Narasi yang sudah saya koreksi.')
            ->assertDontSee('GMRT · Diolah aplikasi · CC BY 4.0');
        $image = $proposal->attachments()->where('mime_type', 'image/png')->firstOrFail();
        $this->assertSame('kkprl_private', $image->storage_disk);
        $this->assertSame('tide', $image->anchor_key);
        $this->assertSame($data->id, $image->source_metadata['tide_chart']['source_attachment_id']);
        $this->assertSame($data->checksum, $image->source_metadata['tide_chart']['source_checksum']);
        $this->assertSame(1.5, $image->source_metadata['tide_chart']['summary']['maximum_m']);
        $this->assertSame('Nol palem stasiun', $image->source_metadata['tide_chart']['summary']['datum']);
        $this->get(route('kkprl.proposal.attachment.download', $image))->assertOk()->assertHeader('content-type', 'image/png');
        $this->assertSame([], $proposal->fresh()->payload ?? []);
        Http::assertNothingSent();
        session()->forget(KkprlProposalAccessService::SELECTION_KEY);
        $this->get(route('kkprl.proposal.attachment.download', $image))->assertNotFound();
    }

    #[Test]
    public function chart_generation_is_idempotent_and_uses_the_latest_table(): void
    {
        $proposal = $this->draft();
        $this->upload($proposal);
        $older = $proposal->attachments()->firstOrFail();
        $component = $this->upload($proposal, "waktu,tinggi\n2026-10-01 00:00,400\n2026-10-01 01:00,600\n");
        $latest = $proposal->attachments()->latest('id')->firstOrFail();
        $component->call('generateTideChart', $older->id)->assertHasNoErrors()
            ->call('generateTideChart', $older->id)->assertHasNoErrors();
        $this->assertSame(3, $proposal->attachments()->count());
        $image = $proposal->attachments()->where('mime_type', 'image/png')->firstOrFail();
        $this->assertSame($latest->id, $image->source_metadata['tide_chart']['source_attachment_id']);
        $this->assertSame(6, $image->source_metadata['tide_chart']['summary']['maximum_m']);
        $this->assertCount(3, Storage::disk('kkprl_private')->allFiles());
        Http::assertNothingSent();
    }

    #[Test]
    public function chart_rejects_a_changed_source_and_preserves_the_previous_image_and_draft(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $component->call('generateTideChart', $data->id)->assertHasNoErrors();
        $image = $proposal->attachments()->where('mime_type', 'image/png')->firstOrFail();
        Storage::disk('kkprl_private')->put($data->storage_path, "waktu,tinggi\n2026-10-01,100\n2026-10-02,300\n");
        $component->set('previewData.tide', 'Koreksi manual.')
            ->call('generateTideChart', $data->id)->assertSet('previewData.tide', 'Koreksi manual.')
            ->assertSee('File data pasut berubah');
        $this->assertSame($image->checksum, hash('sha256', Storage::disk('kkprl_private')->get($image->storage_path)));
        $this->assertSame(2, $proposal->attachments()->count());
    }

    #[Test]
    public function chart_cannot_be_generated_from_another_proposal_or_a_locked_draft(): void
    {
        $owner = $this->draft();
        $this->upload($owner);
        $foreign = $owner->attachments()->firstOrFail();
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $component->call('generateTideChart', $foreign->id)->assertStatus(404);
        $proposal->update(['status' => 'submitted']);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->call('generateTideChart', $data->id)->assertStatus(409);
        $this->assertSame(1, $proposal->attachments()->count());
    }

    #[Test]
    public function chart_refuses_a_tampered_existing_png_instead_of_silently_duplicating_it(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $component->call('generateTideChart', $data->id)->assertHasNoErrors();
        $image = $proposal->attachments()->where('mime_type', 'image/png')->firstOrFail();
        Storage::disk('kkprl_private')->put($image->storage_path, 'tampered image');
        $component->set('previewData.tide', 'Koreksi manual.')
            ->call('generateTideChart', $data->id)->assertSet('previewData.tide', 'Koreksi manual.')
            ->assertSee('Grafik sebelumnya tidak tersedia atau berubah');
        $this->assertSame(2, $proposal->attachments()->count());
        $this->assertSame($data->checksum, hash('sha256', Storage::disk('kkprl_private')->get($data->storage_path)));
    }

    #[Test]
    public function chart_quota_failure_keeps_source_and_manual_draft(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        for ($i = 0; $i < 9; $i++) {
            $copy = $data->replicate();
            $copy->attachment_role = 'supporting';
            $copy->save();
        }
        $component->set('previewData.tide', 'Koreksi manual.')
            ->call('generateTideChart', $data->id)->assertHasErrors(['tideChart'])
            ->assertSet('previewData.tide', 'Koreksi manual.');
        $this->assertSame(10, $proposal->attachments()->count());
        $this->assertCount(1, Storage::disk('kkprl_private')->allFiles());
    }

    #[Test]
    public function chart_image_and_source_provenance_follow_snapshot_word_and_pdf_export(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $component->call('generateTideChart', $data->id)->assertHasNoErrors();
        $payload = [];
        foreach (ProposalProgress::requiredFields('bag-3') as $field) {
            $payload[$field] = KkprlPayloadFixture::value($field);
        }
        $proposal->update(['payload' => ['bag-3' => $payload]]);
        $snapshot = app(KkprlProposalSnapshotService::class)->capture($proposal->fresh(), 'bag-3');
        $this->assertSame($data->checksum, $snapshot->attachments[1]['source_metadata']['tide_chart']['source_checksum']);
        $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-3');
        $zip = new \ZipArchive;
        $zip->open(Storage::disk('kkprl_private')->path($documents->firstWhere('format', 'docx')->storage_path));
        $xml = $zip->getFromName('word/document.xml');
        $images = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_starts_with($zip->getNameIndex($i), 'word/media/')) {
                $images[] = $zip->getFromIndex($i);
            }
        }
        $zip->close();
        $this->assertNotEmpty($images);
        $graph = $proposal->attachments()->where('mime_type', 'image/png')->firstOrFail();
        $this->assertContains($graph->checksum, array_map(fn ($bytes) => hash('sha256', $bytes), $images));
        $this->assertStringContainsString('Grafik pasang surut', $xml);
        $this->assertStringContainsString('Asia/Jayapura', $xml);
        $binary = Storage::disk('kkprl_private')->get($documents->firstWhere('format', 'pdf')->storage_path);
        $this->assertStringContainsString('/Width 1600', $binary);
        $this->assertStringContainsString('/Height 1080', $binary);
        $pdf = (new Parser)->parseContent($binary)->getText();
        $this->assertStringContainsString('Grafik pasang surut', $pdf);
        $this->assertStringContainsString('Nol palem stasiun', $pdf);
    }

    #[Test]
    public function backend_computes_formzahl_and_creates_a_reviewable_draft_without_gemini(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal, $this->harmonicCsv())
            ->assertSee('Hitung data pasut')->assertSee('Rapikan narasi dengan AI');
        $data = $proposal->attachments()->firstOrFail();
        $component->call('calculateTideData', $data->id)->assertHasNoErrors()
            ->assertSet('errorMessage', null)
            ->assertSet('previewData.tide', fn ($text) => str_contains($text, 'Formzahl') && str_contains($text, '0,500'));
        $summary = $data->fresh()->source_metadata['tide_summary'];
        $this->assertSame(745, $summary['count']);
        $this->assertEqualsWithDelta(0.5, $summary['harmonic']['formzahl'], 1e-8);
        $this->assertSame('mixed_semidiurnal', $summary['harmonic']['tidal_type']);
        $this->assertEmpty($proposal->fresh()->payload);
        Http::assertNothingSent();
        $component->call('confirmAndSave')->assertHasNoErrors();
        $this->assertStringContainsString('Formzahl', $proposal->fresh()->payload['bag-3']['tide']);
    }

    #[Test]
    public function backend_keeps_statistics_available_when_harmonic_data_is_too_short(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $component->call('calculateTideData', $data->id)->assertHasNoErrors()
            ->assertSet('previewData.tide', fn ($text) => str_contains($text, '3 sampel') && str_contains($text, 'belum dapat ditetapkan'));
        $this->assertNull($data->fresh()->source_metadata['tide_summary']['harmonic']['formzahl']);
        Http::assertNothingSent();
    }

    #[Test]
    public function optional_ai_timeout_preserves_the_backend_narrative_and_calculation(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal, $this->harmonicCsv());
        $data = $proposal->attachments()->firstOrFail();
        $component->call('calculateTideData', $data->id)->assertHasNoErrors();
        $text = $component->get('previewData.tide');
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Timeout'));
        $component->call('analyzeTideData', $data->id)->assertSet('previewData.tide', $text)
            ->assertSee('tetap tersedia');
        $this->assertEqualsWithDelta(0.5, $data->fresh()->source_metadata['tide_summary']['harmonic']['formzahl'], 1e-8);
        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function cached_backend_results_still_require_original_bytes_coordinates_access_and_an_editable_proposal(): void
    {
        $proposal = $this->draft();
        $csv = $this->harmonicCsv();
        $component = $this->upload($proposal, $csv);
        $data = $proposal->attachments()->firstOrFail();
        $component->call('calculateTideData', $data->id)->assertSet('errorMessage', null);
        $text = $component->get('previewData.tide');
        $summary = $data->fresh()->source_metadata['tide_summary'];
        $component->call('calculateTideData', $data->id)->assertSet('previewData.tide', $text);
        Storage::disk('kkprl_private')->put($data->storage_path, 'changed');
        $component->call('calculateTideData', $data->id)->assertSee('File data pasut berubah')
            ->assertSet('previewData.tide', $text);
        Storage::disk('kkprl_private')->put($data->storage_path, $csv);
        $proposal->update(['payload' => ['bag-1' => ['coordinates' => 'lokasi berubah']]]);
        $component->call('calculateTideData', $data->id)->assertSee('Koordinat Bab 1 berubah')
            ->assertSet('previewData.tide', $text);
        $this->assertSame($summary, $data->fresh()->source_metadata['tide_summary']);
        $deniedComponent = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3']);
        $proposal->update(['status' => 'submitted']);
        $component->call('calculateTideData', $data->id)->assertStatus(409);
        app(KkprlProposalAccessService::class)->forget();
        $deniedComponent->call('calculateTideData', $data->id)->assertForbidden();
        Http::assertNothingSent();
    }

    #[Test]
    public function tidal_form_is_visible_only_for_tides_and_upload_preserves_private_data_and_source(): void
    {
        $proposal = $this->draft();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->assertSee('Pilih gambar')->assertDontSee('Pilih file data pasut');
        $this->upload($proposal)->assertSee('Pilih file data pasut')->assertSee('Hitung data pasut')->assertSee('Rapikan narasi dengan AI');
        $data = $proposal->attachments()->firstOrFail();
        $this->assertSame('ai_tide_data', $data->attachment_role);
        $this->assertSame('tide', $data->field);
        $this->assertSame('big', $data->source_metadata['source']);
        $this->assertSame(3, $data->source_metadata['tide_summary']['count']);
        $this->assertEquals(1, $data->source_metadata['tide_summary']['mean_m']);
        Storage::disk('kkprl_private')->assertExists($data->storage_path);
        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function srgi_csv_upload_selects_utc_metres_and_preserves_station_and_prediction_context(): void
    {
        $proposal = $this->draft();
        $csv = file_get_contents(base_path('tests/Fixtures/kkprl/srgi-tide.csv'));
        $component = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('evidenceField', 'tide')
            ->set('tideUnit', 'mm')->set('tideTimezone', 'Asia/Jakarta')->set('tideDateFormat', 'dmy')
            ->set('tideFile', UploadedFile::fake()->createWithContent('data_srgi.csv', $csv))
            ->assertHasNoErrors()->assertSet('tideDateColumn', '0')->assertSet('tideHeightColumn', '2')
            ->assertSet('tideTimeColumn', '')->assertSet('tideTimezone', 'UTC')->assertSet('tideUnit', 'm')
            ->assertSet('tideDateFormat', 'ymd')->assertSet('tideDatum', 'MSL')->assertSee('SRGI BIG')
            ->call('uploadTideData')->assertHasNoErrors();
        $data = $proposal->attachments()->firstOrFail();
        $this->assertSame('PAAM', $data->source_metadata['tide_file_metadata']['station_name']);
        $this->assertSame('prediction', $data->source_metadata['tide_summary']['data_kind']);
        $this->assertEquals(0.64275, $data->source_metadata['tide_summary']['mean_m']);
        $this->assertSame($csv, Storage::disk('kkprl_private')->get($data->storage_path));
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"tide":"Ulasan prediksi pasang surut dari BIG."}']]]]]])]);
        $component->call('analyzeTideData', $data->id)->assertHasNoErrors()
            ->assertSet('previewData.tide', fn ($value) => str_contains($value, 'bukan pengukuran langsung'));
        Http::assertSent(fn ($request) => str_contains($request['system_instruction']['parts'][0]['text'], '"data_kind":"prediction"')
            && str_contains($request['system_instruction']['parts'][0]['text'], '"station_name":"PAAM"'));
        $this->assertEmpty($proposal->fresh()->payload);
    }

    public static function srgiTimezoneSelections(): array
    {
        return [['UTC', '0', '2026-09-01 00:00:00'], ['Asia/Jayapura', '1', '2026-09-01 09:00:00']];
    }

    #[Test]
    #[DataProvider('srgiTimezoneSelections')]
    public function selecting_a_timezone_uses_its_matching_srgi_timestamp_column(string $timezone, string $column, string $start): void
    {
        $proposal = $this->draft();
        $component = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('evidenceField', 'tide')
            ->set('tideFile', UploadedFile::fake()->createWithContent('srgi.csv', file_get_contents(base_path('tests/Fixtures/kkprl/srgi-tide.csv'))))
            ->set('tideTimezone', $timezone)->assertSet('tideDateColumn', $column)
            ->assertSet('tideTimeColumn', '')->call('uploadTideData')->assertHasNoErrors();
        $data = $proposal->attachments()->firstOrFail();
        $this->assertSame((int) $column, $data->source_metadata['tide_options']['date_column']);
        $this->assertNull($data->source_metadata['tide_options']['time_column']);
        $this->assertSame($timezone, $data->source_metadata['tide_summary']['timezone']);
        $this->assertSame($start, $data->source_metadata['tide_summary']['start']);
        $this->assertSame(4, $data->source_metadata['tide_summary']['count']);
        $this->assertEquals(0.64275, $data->source_metadata['tide_summary']['mean_m']);
        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function srgi_date_column_and_timezone_stay_in_sync_in_both_directions(): void
    {
        $proposal = $this->draft();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('evidenceField', 'tide')
            ->set('tideFile', UploadedFile::fake()->createWithContent('srgi.csv', file_get_contents(base_path('tests/Fixtures/kkprl/srgi-tide.csv'))))
            ->set('tideTimezone', 'Asia/Jayapura')->assertSet('tideDateColumn', '1')
            ->set('tideTimezone', 'UTC')->assertSet('tideDateColumn', '0')
            ->set('tideDateColumn', '1')->assertSet('tideTimezone', 'Asia/Jayapura')
            ->set('tideTimeColumn', '0')->assertSet('tideTimeColumn', '')
            ->set('tideDateColumn', '0')->assertSet('tideTimezone', 'UTC')
            ->set('tideTimezone', 'Asia/Jayapura')->call('uploadTideData')->assertHasNoErrors();
        $this->assertSame('2026-09-01 09:00:00', $proposal->attachments()->firstOrFail()->source_metadata['tide_summary']['start']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function a_monthly_minute_file_is_uploaded_and_analyzed_using_all_samples(): void
    {
        $proposal = $this->draft();
        $time = new \DateTimeImmutable('2026-10-01 00:00:00');
        $csv = "waktu,tinggi\n";
        for ($i = 0; $i < 44641; $i++) {
            $csv .= $time->format('Y-m-d H:i').','.(($i % 3 + 1) * 100)."\n";
            $time = $time->modify('+1 minute');
        }
        $component = $this->upload($proposal, $csv)->assertSee('50.000')->assertSee('44.641');
        $data = $proposal->attachments()->firstOrFail();
        $summary = $data->source_metadata['tide_summary'];
        $this->assertSame(44641, $summary['count']);
        $this->assertSame(60, $summary['interval_min_seconds']);
        $this->assertSame(60, $summary['interval_max_seconds']);
        $this->assertEquals(744, $summary['duration_hours']);
        $this->assertSame('2026-11-01 00:00:00', $summary['end']);
        $this->assertSame($csv, Storage::disk('kkprl_private')->get($data->storage_path));
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"tide":"Analisis data pasut bulanan."}']]]]]])]);
        $component->call('analyzeTideData', $data->id)->assertHasNoErrors()
            ->assertSet('previewData.tide', fn ($value) => str_contains($value, '44641 sampel') && str_contains($value, 'Analisis data pasut bulanan.'));
        Http::assertSent(fn ($request) => str_contains($request['system_instruction']['parts'][0]['text'], '"count":44641')
            && strlen($request->body()) < 20000);
        Http::assertSentCount(1);
        $this->assertEmpty($proposal->fresh()->payload);
    }

    #[Test]
    public function table_is_prioritized_even_when_analysis_is_requested_from_a_tidal_image(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $component->set('evidenceFile', UploadedFile::fake()->image('pasut.png'))
            ->call('uploadEvidence')->assertHasNoErrors();
        $image = $proposal->attachments()->where('attachment_role', 'ai_evidence')->firstOrFail();
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"tide":"Data pasut menunjukkan perubahan tinggi selama periode data."}']]]]]])]);
        $component->set('previewData.waves', 'Koreksi gelombang.')
            ->call('analyzeEvidence', $image->id)->assertHasNoErrors()
            ->assertSet('previewData.tide', fn ($value) => str_contains($value, 'Data pasut menunjukkan') && str_contains($value, '1,500 m') && str_contains($value, 'bukan MSL'))
            ->assertSet('previewData.waves', 'Koreksi gelombang.');
        Http::assertSent(fn ($request) => ! isset($request['contents'][0]['parts'][1]['inline_data'])
            && str_contains($request['system_instruction']['parts'][0]['text'], '"maximum_m":1.5'));
        $this->assertEmpty($proposal->fresh()->payload);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->assertSet('previewData.tide', fn ($value) => str_contains($value, '1,500 m'))->call('confirmAndSave');
        $this->assertStringContainsString('1,500 m', $proposal->fresh()->payload['bag-3']['tide']);
    }

    #[Test]
    public function invalid_data_and_ai_failures_preserve_existing_narrative_and_files(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $this->mock(KkprlAiService::class, fn ($mock) => $mock->shouldReceive('chat')->once()->andReturn(null));
        $component->set('previewData.tide', 'Narasi manual.')
            ->call('analyzeTideData', $data->id)->assertSet('previewData.tide', 'Narasi manual.')
            ->assertSee('Data dan narasi sebelumnya tetap tersedia');
        $component->set('tideFile', UploadedFile::fake()->createWithContent('pasut.csv', "waktu,tinggi\n2026-10-01 00:00,rusak\n2026-10-01 01:00,1\n"))
            ->call('uploadTideData')->assertHasErrors('tideFile');
        $this->assertSame(1, $proposal->attachments()->count());
        Storage::disk('kkprl_private')->assertExists($data->storage_path);
    }

    #[Test]
    public function tidal_analysis_sends_a_focused_prompt_and_timeout_preserves_the_existing_draft(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $component->set('previewData.tide', 'Koreksi pasut pengguna.')
            ->set('previewData.waves', 'NARASI_GELOMBANG_YANG_TIDAK_DIPERLUKAN');
        $attempts = 0;
        Http::fake(function ($request) use (&$attempts) {
            $attempts++;
            $prompt = $request['system_instruction']['parts'][0]['text'];
            $this->assertStringContainsString('Koreksi pasut pengguna.', $prompt);
            $this->assertStringNotContainsString('NARASI_GELOMBANG_YANG_TIDAK_DIPERLUKAN', $prompt);
            $this->assertSame('LOW', $request['generationConfig']['thinkingConfig']['thinkingLevel'] ?? null);

            throw new ConnectionException('cURL error 28: Timeout', 0,
                new ConnectTimeoutException('cURL error 28: Timeout', new Request('POST', 'https://example.test')));
        });
        $component->call('analyzeTideData', $data->id)
            ->assertSet('previewData.tide', 'Koreksi pasut pengguna.')
            ->assertSet('previewData.waves', 'NARASI_GELOMBANG_YANG_TIDAK_DIPERLUKAN')
            ->assertSee('waktu tunggu')->assertSee('Data dan narasi sebelumnya tetap tersedia');
        $this->assertEmpty($proposal->fresh()->payload);
        Storage::disk('kkprl_private')->assertExists($data->storage_path);
        $this->assertSame(1, $attempts);
    }

    #[Test]
    public function tables_cannot_be_used_in_other_chapters_or_other_proposals(): void
    {
        $first = $this->draft();
        $this->upload($first);
        $data = $first->attachments()->firstOrFail();
        $second = $this->draft();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $second->id, 'chapter' => 'bag-3'])
            ->call('analyzeTideData', $data->id)->assertNotFound();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $second->id, 'chapter' => 'bag-3'])
            ->call('calculateTideData', $data->id)->assertNotFound();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $second->id, 'chapter' => 'bag-2'])
            ->call('calculateTideData', $data->id)->assertForbidden();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $second->id, 'chapter' => 'bag-2'])
            ->call('uploadTideData')->assertForbidden();
        Http::assertNothingSent();
    }

    #[Test]
    public function changes_to_coordinates_or_file_identity_stop_analysis(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        Storage::disk('kkprl_private')->put($data->storage_path, 'changed');
        $component->call('analyzeTideData', $data->id)->assertSee('File data pasut berubah');
        $proposal->update(['payload' => ['bag-1' => ['coordinates' => 'lokasi berubah']]]);
        $component->call('analyzeTideData', $data->id)->assertSee('Koordinat Bab 1 berubah');
        Http::assertNothingSent();
    }

    #[Test]
    public function newer_table_uploaded_during_analysis_prevents_old_results_from_overwriting_preview(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $this->mock(KkprlAiService::class, function ($mock) use ($data) {
            $mock->shouldReceive('chat')->once()->andReturnUsing(function () use ($data) {
                $copy = $data->replicate();
                $copy->save();

                return '{"tide":"Hasil lama"}';
            });
        });
        $component->set('previewData.tide', 'Narasi terbaru.')
            ->call('analyzeTideData', $data->id)->assertSet('previewData.tide', 'Narasi terbaru.')
            ->assertSee('Data pasut berubah');
    }

    public static function exportCalculationModes(): array
    {
        return [[false], [true]];
    }

    #[Test]
    #[DataProvider('exportCalculationModes')]
    public function spreadsheet_evidence_exports_as_a_calculation_summary_in_word_and_pdf(bool $withHarmonics): void
    {
        $proposal = $this->draft();
        if ($withHarmonics) {
            $component = $this->upload($proposal, $this->harmonicCsv());
            $component->call('calculateTideData', $proposal->attachments()->firstOrFail()->id)->assertHasNoErrors();
        } else {
            $this->upload($proposal);
        }
        $payload = [];
        foreach (ProposalProgress::requiredFields('bag-3') as $field) {
            $payload[$field] = KkprlPayloadFixture::value($field);
        }
        $proposal->update(['payload' => ['bag-3' => $payload]]);
        $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-3');
        $this->assertCount(2, $documents);
        $zip = new \ZipArchive;
        $zip->open(Storage::disk('kkprl_private')->path($documents->firstWhere('format', 'docx')->storage_path));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertStringContainsString('Ringkasan data pasut', $xml);
        $this->assertStringContainsString($withHarmonics ? '0,500' : '1,500 m', $xml);
        if ($withHarmonics) {
            $this->assertStringContainsString('Formzahl', $xml);
            $this->assertStringContainsString('bukan konstanta harmonik astronomis', $xml);
        }
        $this->assertStringNotContainsString('Halaman PDF', $xml);
        $pdf = (new Parser)->parseContent(Storage::disk('kkprl_private')->get($documents->firstWhere('format', 'pdf')->storage_path))->getText();
        $this->assertStringContainsString('Ringkasan data pasut', $pdf);
        $this->assertStringContainsString($withHarmonics ? '0,500' : '1,500 m', $pdf);
        if ($withHarmonics) {
            $this->assertStringContainsString('Formzahl', $pdf);
            $this->assertStringContainsString('bukan konstanta harmonik astronomis', preg_replace('/\s+/', ' ', $pdf));
        }
        $this->assertStringNotContainsString('Halaman PDF', $pdf);
    }

    #[Test]
    public function unrelated_ai_fields_and_clarification_do_not_overwrite_tide_preview(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $this->mock(KkprlAiService::class, fn ($mock) => $mock->shouldReceive('chat')->twice()->andReturn(
            '{"tide":"Baru","waves":"Tidak diminta"}', 'Apakah acuan tinggi stasiun sudah diketahui?'));
        $component->set('previewData.tide', 'Narasi manual.')
            ->call('analyzeTideData', $data->id)->assertSet('previewData.tide', 'Narasi manual.')
            ->assertSee('Narasi sebelumnya tetap tersedia')
            ->call('analyzeTideData', $data->id)->assertSet('previewData.tide', 'Narasi manual.')
            ->assertSee('Apakah acuan tinggi stasiun sudah diketahui?');
    }

    #[Test]
    public function latest_preview_correction_and_lock_are_rechecked_after_provider_returns(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $this->mock(KkprlAiService::class, function ($mock) use ($proposal) {
            $mock->shouldReceive('chat')->once()->andReturnUsing(function () use ($proposal) {
                KkprlProposalChat::updateOrCreate(['proposal_id' => $proposal->id, 'chapter' => 'bag-3'],
                    ['messages' => [['role' => 'system_preview', 'data' => ['tide' => 'Koreksi terbaru.']]]]);

                return '{"tide":"Hasil lama"}';
            });
        });
        $component->call('analyzeTideData', $data->id)->assertSet('previewData.tide', 'Koreksi terbaru.')->assertSee('Pratinjau berubah');
        $this->mock(KkprlAiService::class, function ($mock) use ($proposal) {
            $mock->shouldReceive('chat')->once()->andReturnUsing(function () use ($proposal) {
                $proposal->update(['status' => 'submitted']);

                return '{"tide":"Tidak boleh disimpan"}';
            });
        });
        $component->call('analyzeTideData', $data->id)->assertStatus(409);
        $this->assertArrayNotHasKey('bag-3', $proposal->fresh()->payload ?? []);
    }

    #[Test]
    public function table_upload_obeys_shared_attachment_quota_and_private_download_access(): void
    {
        $proposal = $this->draft();
        $component = $this->upload($proposal);
        $data = $proposal->attachments()->firstOrFail();
        $this->get(route('kkprl.proposal.attachment.download', $data))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        for ($i = 1; $i < 10; $i++) {
            app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-3', UploadedFile::fake()->image('photo.png'));
        }
        $component->set('tideFile', UploadedFile::fake()->createWithContent('pasut.csv', "waktu,tinggi\n2026-10-01 00:00,1\n2026-10-01 01:00,2\n"))
            ->call('uploadTideData')->assertHasErrors('tideFile');
        $this->assertSame(10, $proposal->attachments()->count());
        app(KkprlProposalAccessService::class)->forget();
        $this->get(route('kkprl.proposal.attachment.download', $data))->assertNotFound();
    }
}
