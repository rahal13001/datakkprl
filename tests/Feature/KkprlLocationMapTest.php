<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalLocationGeometry;
use App\Domain\Kkprl\ProposalLocked;
use App\Domain\Kkprl\ProposalPayloadSanitizer;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlProposalWizard;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalDocument;
use App\Services\KkprlLocationMapRenderer;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalSnapshotService;
use App\Services\KkprlWorldCoverTiles;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Processors\SQLiteProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Smalot\PdfParser\Parser;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;
use ZipArchive;

class KkprlLocationMapTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('kkprl_private');
        Http::preventStrayRequests();
        config(['kkprl.location_map.basemap' => 'worldcover-2021']);
    }

    private function tile(): string
    {
        $image = imagecreatetruecolor(256, 256);
        imagefill($image, 0, 0, imagecolorallocate($image, 61, 104, 74));
        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return $binary;
    }

    private function proposal(array $extra = []): KkprlProposal
    {
        $payload = [];
        foreach (ProposalProgress::requiredFields('bag-1') as $field) {
            $payload[$field] = KkprlPayloadFixture::value($field);
        }

        return $this->ownedProposal(['payload' => array_replace($payload, $extra)]);
    }

    #[Test]
    public function satellite_cache_round_trips_through_a_utf8_text_column_with_mysql_serialization(): void
    {
        // Isolated memory database, MySQL DatabaseStore serializer, UTF-8 column constraint.
        $pdo = new \PDO('sqlite::memory:');
        $pdo->sqliteCreateFunction('valid_utf8', fn ($value) => mb_check_encoding($value, 'UTF-8') ? 1 : 0, 1);
        $pdo->exec('CREATE TABLE tile_cache ("key" TEXT PRIMARY KEY, value TEXT NOT NULL CHECK(valid_utf8(value)), expiration INTEGER NOT NULL)');
        $connection = new MySqlConnection($pdo, ':memory:');
        $connection->setQueryGrammar(new SQLiteGrammar($connection));
        $connection->setPostProcessor(new SQLiteProcessor);
        Cache::swap(new Repository(new DatabaseStore($connection, 'tile_cache', 'fixture-')));
        $png = $this->tile();
        Http::fake(['wmts.terrascope.be/*' => Http::response($png)]);
        $provider = app(KkprlWorldCoverTiles::class);
        $this->assertSame($png, $provider->tile(14, 123, 456, microtime(true) + 20));
        $cached = $pdo->query('SELECT value FROM tile_cache')->fetchColumn();
        $this->assertTrue(mb_check_encoding($cached, 'UTF-8'));
        $this->assertSame($png, $provider->tile(14, 123, 456, microtime(true) + 20));
        Http::assertSentCount(1);
    }

    #[Test]
    public function livewire_exports_complete_bab_one_with_mysql_text_cache_while_other_chapters_are_incomplete(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->sqliteCreateFunction('valid_utf8', fn ($value) => mb_check_encoding($value, 'UTF-8') ? 1 : 0, 1);
        $pdo->exec('CREATE TABLE tile_cache ("key" TEXT PRIMARY KEY, value TEXT NOT NULL CHECK(valid_utf8(value)), expiration INTEGER NOT NULL)');
        $connection = new MySqlConnection($pdo, ':memory:');
        $connection->setQueryGrammar(new SQLiteGrammar($connection));
        $connection->setPostProcessor(new SQLiteProcessor);
        Cache::swap(new Repository(new DatabaseStore($connection, 'tile_cache', 'fixture-')));
        Http::fake(['wmts.terrascope.be/*' => Http::response($this->tile())]);
        $proposal = $this->proposal();
        $progress = app(ProposalProgress::class)->evaluate($proposal->payload);
        $this->assertSame('complete', $progress->chapters['bag-1']['status']);
        $this->assertSame('incomplete', $progress->chapters['bag-2']['status']);
        $this->assertSame('incomplete', $progress->chapters['bag-3']['status']);
        app(KkprlProposalAccessService::class)->grantAccess($proposal);
        Livewire::test(KkprlProposalWizard::class)
            ->call('generateChapterDocuments', 'bag-1')
            ->assertHasNoErrors()
            ->assertSet('documentMessage', 'Word dan PDF bag-1 berhasil dibuat.');
        $documents = $proposal->documents()->where('generation_status', 'generated')->get();
        $this->assertCount(2, $documents);
        foreach ($documents as $document) {
            Storage::disk('kkprl_private')->assertExists($document->storage_path);
        }
        $this->assertSame($proposal->payload, $proposal->fresh()->payload);
    }

    #[Test]
    public function geometry_measures_and_handles_the_antimeridian_without_changing_dimensions(): void
    {
        $geometry = ProposalLocationGeometry::fromValues(['shape_type' => 'line', 'coordinates_raw' => json_encode([
            ['lat_dd' => 0, 'lng_dd' => 179.99], ['lat_dd' => 0, 'lng_dd' => -179.99],
        ])]);
        $this->assertEqualsWithDelta(2.2239, $geometry->measurement()['value'], .001);
        $this->assertSame('2,22 km', $geometry->measurement()['label']);
        $this->assertLessThan(.001, abs($geometry->points[1][0] - $geometry->points[0][0]));
        $polygon = ProposalLocationGeometry::fromValues(['shape_type' => 'polygon', 'coordinates_raw' => json_encode([
            ['lat_dd' => 0, 'lng_dd' => 0], ['lat_dd' => 0, 'lng_dd' => .01],
            ['lat_dd' => .01, 'lng_dd' => .01], ['lat_dd' => .01, 'lng_dd' => 0],
        ])]);
        $this->assertEqualsWithDelta(123.643, $polygon->measurement()['value'], .01);
        $this->assertSame('123,64 ha', $polygon->measurement()['label']);
    }

    #[Test]
    public function renders_bounded_satellite_png_and_uses_only_fixed_provider_queries_and_cache(): void
    {
        Http::fake(['wmts.terrascope.be/*' => Http::response($this->tile(), 200, ['Content-Type' => 'image/png'])]);
        $values = ['shape_type' => 'point', 'coordinates' => KkprlPayloadFixture::value('coordinates')];
        $renderer = app(KkprlLocationMapRenderer::class);
        $map = $renderer->render($values);
        $this->assertSame([1800, 1100], $map['dimensions']);
        $this->assertSame('image/png', getimagesizefromstring($map['binary'])['mime']);
        $count = count(Http::recorded());
        $this->assertGreaterThan(0, $count);
        $this->assertLessThanOrEqual(16, $count);
        Http::assertSent(fn ($request) => $request['LAYER'] === 'esa-worldcover-s2rgbnir-10m-2021-v2_tcc' && $request['TIME'] === '2021-01-01');
        $this->assertSame($map['binary'], $renderer->render($values)['binary']);
        Http::assertSentCount($count);
    }

    #[Test]
    public function detail_is_clear_and_inset_uses_satellite_without_changing_source_geometry(): void
    {
        Http::fake(['wmts.terrascope.be/*' => Http::response($this->tile())]);
        $values = ['shape_type' => 'line', 'coordinates_raw' => '[{"lat_dd":-0.88,"lng_dd":131.256},{"lat_dd":-0.88,"lng_dd":131.2564}]'];
        $before = ProposalLocationGeometry::fromValues($values);
        $map = app(KkprlLocationMapRenderer::class)->render($values);
        $this->assertSame(700, $map['print_width_points']);
        $this->assertSame(['detail' => 500, 'inset' => 50000], $map['scales']);
        $image = imagecreatefromstring($map['binary']);
        $this->assertSame([238, 242, 246], array_values(array_slice(imagecolorsforindex($image, imagecolorat($image, 120, 150)), 0, 3)));
        $this->assertSame([61, 104, 74], array_values(array_slice(imagecolorsforindex($image, imagecolorat($image, 1340, 800)), 0, 3)));
        imagedestroy($image);
        $after = ProposalLocationGeometry::fromValues($values);
        $this->assertSame($before->latLng, $after->latLng);
        $this->assertSame($before->measurement(), $after->measurement());
    }

    #[Test]
    public function grid_inset_works_offline_and_new_layout_preserves_previous_map_snapshot(): void
    {
        config(['kkprl.location_map.basemap' => 'none']);
        $map = app(KkprlLocationMapRenderer::class)->render(['shape_type' => 'point', 'coordinates' => KkprlPayloadFixture::value('coordinates')]);
        $this->assertSame(['detail' => 500, 'inset' => 50000], $map['scales']);
        $proposal = $this->proposal();
        $service = app(KkprlProposalSnapshotService::class);
        $old = $service->capture($proposal, 'bag-1', '2026-10-12-map');
        $this->assertSame(['coordinates_raw', 'map_source', 'basemap'], array_keys($old->payload['location_map']));
        $document = new KkprlProposalDocument([
            'chapter' => 'bag-1', 'snapshot_payload_encrypted' => $old->payload,
        ]);
        $this->assertSame($old->snapshotHash, $service->captureForDocument($proposal, $document)->snapshotHash);
        $new = $service->capture($proposal, 'bag-1');
        $this->assertSame('2026-10-12-map-osm', $new->payload['document_layout_version']);
        $this->assertSame('osm-detail-inset-v2', $new->payload['location_map']['layout']);
        $this->assertNotSame($old->snapshotHash, $new->snapshotHash);
        Http::assertNothingSent();
    }

    #[Test]
    public function word_and_pdf_embed_the_same_map_on_a_dedicated_landscape_page_and_preserve_idempotency(): void
    {
        Http::fake(['wmts.terrascope.be/*' => Http::response($this->tile(), 200, ['Content-Type' => 'image/png'])]);
        $proposal = $this->proposal(['map_source' => 'Survei koordinat contoh']);
        $service = app(KkprlProposalDocumentService::class);
        $documents = $service->generateChapterFormats($proposal, 'bag-1');
        $zip = new ZipArchive;
        $zip->open(Storage::disk('kkprl_private')->path($documents->firstWhere('format', 'docx')->storage_path));
        $xml = $zip->getFromName('word/document.xml');
        $this->assertStringContainsString('w:orient="landscape"', $xml);
        $this->assertStringContainsString('PETA LOKASI KEGIATAN', $xml);
        $this->assertSame(1, substr_count($xml, '<w:pgNumType w:start="1"/>'));
        $this->assertNotFalse($zip->getFromName('word/media/image1.png'));
        // Same physical image width as PDF: 700 pt = 8,890,000 EMU.
        $this->assertStringContainsString('<wp:extent cx="8890000"', $xml);
        $zip->close();
        $pdf = (new Parser)->parseContent(Storage::disk('kkprl_private')->get($documents->firstWhere('format', 'pdf')->storage_path));
        $pages = $pdf->getPages();
        $first = $pages[0]->getDetails()['MediaBox'];
        $last = $pages[array_key_last($pages)]->getDetails()['MediaBox'];
        $this->assertLessThan($first[3], $first[2]);
        $this->assertGreaterThan($last[3], $last[2]);
        $this->assertStringContainsString('PETA LOKASI KEGIATAN', $pdf->getText());
        $this->assertSame($documents->pluck('id')->all(), $service->generateChapterFormats($proposal->fresh(), 'bag-1')->pluck('id')->all());
    }

    #[Test]
    public function previous_layout_snapshot_remains_valid_and_raw_coordinates_and_source_are_versioned(): void
    {
        $proposal = $this->proposal();
        $service = app(KkprlProposalSnapshotService::class);
        $old = $service->capture($proposal, 'bag-1', '2026-10-11');
        $new = $service->capture($proposal, 'bag-1');
        $proposal->update(['payload' => array_replace($proposal->payload, ['map_source' => 'Survei contoh'])]);
        $this->assertSame($old->snapshotHash, $service->capture($proposal->fresh(), 'bag-1', '2026-10-11')->snapshotHash);
        $this->assertNotSame($new->snapshotHash, $service->capture($proposal->fresh(), 'bag-1')->snapshotHash);
        Http::assertNothingSent();
    }

    #[Test]
    public function revoked_approval_during_tile_request_prevents_document_or_private_map_write(): void
    {
        $proposal = $this->proposal();
        Http::fake(function () {
            $this->applicant->forceFill(['approved_at' => null])->save();

            return Http::response($this->tile(), 200, ['Content-Type' => 'image/png']);
        });
        try {
            app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-1');
            $this->fail('Revocation must reject persistence.');
        } catch (ProposalAccessDenied $exception) {
            $this->assertDatabaseCount('kkprl_proposal_documents', 0);
            $this->assertSame([], Storage::disk('kkprl_private')->allFiles());
        }
    }

    #[Test]
    public function provider_failure_cannot_generate_a_blank_or_fabricated_map(): void
    {
        Http::fake(['*' => Http::response('invalid image', 200)]);
        $this->expectException(ValidationException::class);
        app(KkprlLocationMapRenderer::class)->render(['shape_type' => 'point', 'coordinates' => KkprlPayloadFixture::value('coordinates')]);
    }

    #[Test]
    public function pending_cross_owner_and_locked_requests_never_fetch_tiles(): void
    {
        $proposal = $this->proposal();
        $service = app(KkprlProposalDocumentService::class);
        $this->applicant->forceFill(['approved_at' => null])->save();
        try {
            $service->generateChapterFormats($proposal, 'bag-1');
            $this->fail('Pending applicant must be rejected.');
        } catch (ProposalAccessDenied) {
            Http::assertNothingSent();
        }
        $this->applicant->forceFill(['approved_at' => now()])->save();
        $proposal->forceFill(['applicant_id' => null])->save();
        try {
            $service->generateChapterFormats($proposal->fresh(), 'bag-1');
            $this->fail('Unowned proposal must be rejected.');
        } catch (ProposalAccessDenied) {
            Http::assertNothingSent();
        }
        $proposal->forceFill(['applicant_id' => $this->applicant->id, 'status' => 'submitted'])->save();
        try {
            $service->generateChapterFormats($proposal->fresh(), 'bag-1');
            $this->fail('Locked proposal must be rejected.');
        } catch (ProposalLocked) {
            Http::assertNothingSent();
        }
        $this->assertDatabaseCount('kkprl_proposal_documents', 0);
    }

    #[Test]
    public function malformed_coordinates_self_intersections_and_polar_points_fail_before_network(): void
    {
        foreach ([
            ['shape_type' => 'line', 'coordinates_raw' => '[42,43]'],
            ['shape_type' => 'point', 'coordinates_raw' => '[{"lat_dd":89,"lng_dd":1}]'],
            ['shape_type' => 'polygon', 'coordinates_raw' => '[{"lat_dd":0,"lng_dd":0},{"lat_dd":1,"lng_dd":1},{"lat_dd":0,"lng_dd":1},{"lat_dd":1,"lng_dd":0}]'],
            ['shape_type' => 'point', 'coordinates_raw' => 'invalid json'],
        ] as $values) {
            try {
                app(KkprlLocationMapRenderer::class)->render($values);
                $this->fail('Invalid geometry must not be rendered.');
            } catch (ValidationException) {
                Http::assertNothingSent();
            }
        }
    }

    #[Test]
    public function oversized_tiles_redirects_and_elapsed_deadlines_are_rejected(): void
    {
        $service = app(KkprlWorldCoverTiles::class);
        Http::fakeSequence()->push(str_repeat('x', 524289), 200)->push('', 302, ['Location' => 'http://127.0.0.1/']);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $service->tile(14, 10, 10, microtime(true) + 20);
                $this->fail('Invalid provider response must be rejected.');
            } catch (ValidationException) {
                $this->assertSame([], Cache::get('kkprl-worldcover-2021:base64-v1:14:10:10', []));
            }
        }
        Http::fake();
        try {
            $service->tile(14, 10, 10, microtime(true) - 1);
            $this->fail('Elapsed deadline must not fetch a tile.');
        } catch (ValidationException) {
            Http::assertNothingSent();
        }
    }

    #[Test]
    public function changed_raw_coordinates_during_fetch_cannot_persist_a_stale_document(): void
    {
        $proposal = $this->proposal();
        Http::fake(function () use ($proposal) {
            $proposal->update(['payload' => array_replace($proposal->payload, ['coordinates_raw' => '[{"lat_dd":-6.201,"lng_dd":106.801}]'])]);

            return Http::response($this->tile(), 200);
        });
        try {
            app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-1');
            $this->fail('Changed source must reject stale persistence.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Proposal changed during rendering.', $exception->getMessage());
            $this->assertDatabaseMissing('kkprl_proposal_documents', ['generation_status' => 'generated']);
            $this->assertSame([], Storage::disk('kkprl_private')->allFiles());
        }
    }

    #[Test]
    public function optional_source_survives_sanitization_without_affecting_completion(): void
    {
        $proposal = $this->proposal(['map_source' => 'Survei contoh']);
        $sanitizer = app(ProposalPayloadSanitizer::class);
        $this->assertSame('Survei contoh', $sanitizer->sanitize($proposal->payload)['map_source']);
        $this->assertSame('Survei contoh', $sanitizer->sanitize(['bag-1' => ['map_source' => 'Survei contoh']])['bag-1']['map_source']);
        $this->assertSame('complete', app(ProposalProgress::class)->evaluate($proposal->payload)->chapters['bag-1']['status']);
        $this->expectException(ValidationException::class);
        $sanitizer->sanitize(['bag-1' => ['map_source' => str_repeat('a', 301)]]);
    }

    #[Test]
    public function corrupt_tile_header_cannot_poison_cache_and_a_bad_cache_entry_is_replaced(): void
    {
        $binary = $this->tile();
        $corrupt = substr($binary, 0, 33);
        $this->assertSame(256, getimagesizefromstring($corrupt)[0]);
        $service = app(KkprlWorldCoverTiles::class);
        Http::fakeSequence()->push($corrupt, 200)->push($binary, 200);
        try {
            $service->tile(14, 10, 10, microtime(true) + 20);
            $this->fail('Unreadable PNG must not poison cache.');
        } catch (ValidationException) {
            $this->assertNull(Cache::get('kkprl-worldcover-2021:base64-v1:14:10:10'));
        }
        Cache::put('kkprl-worldcover-2021:base64-v1:14:10:10', base64_encode($corrupt), 60);
        $this->assertSame($binary, $service->tile(14, 10, 10, microtime(true) + 20));
        $this->assertSame(base64_encode($binary), Cache::get('kkprl-worldcover-2021:base64-v1:14:10:10'));
    }

    #[Test]
    public function invalid_or_oversized_encoded_cache_entries_are_replaced_without_using_legacy_binary_cache(): void
    {
        $png = $this->tile();
        Http::fake(['wmts.terrascope.be/*' => Http::response($png)]);
        $key = 'kkprl-worldcover-2021:base64-v1:14:10:10';
        Cache::put('kkprl-worldcover-2021:14:10:10', $png, 60);
        foreach (['', 'not_base64!', str_repeat('a', 699053), base64_encode('invalid PNG')] as $invalid) {
            Cache::put($key, $invalid, 60);
            $this->assertSame($png, app(KkprlWorldCoverTiles::class)->tile(14, 10, 10, microtime(true) + 20));
            $this->assertSame(base64_encode($png), Cache::get($key));
        }
        $this->assertSame($png, Cache::get('kkprl-worldcover-2021:14:10:10'));
        Http::assertSentCount(4);
    }
}
