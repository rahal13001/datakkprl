<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalLocationGeometry;
use App\Domain\Kkprl\ProposalLocationMapViewport;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlProposalWizard;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalDocument;
use App\Services\KkprlLocationMapRenderer;
use App\Services\KkprlOpenStreetMapTiles;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalSnapshotService;
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
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;
use ZipArchive;

class KkprlOpenStreetMapTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('kkprl_private');
        Http::preventStrayRequests();
        config(['kkprl.location_map.main_basemap' => 'openstreetmap', 'kkprl.location_map.basemap' => 'worldcover-2021']);
    }

    private function tile(array $rgb): string
    {
        $image = imagecreatetruecolor(256, 256);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return $binary;
    }

    private function fakeTiles(): void
    {
        Http::fake([
            'tile.openstreetmap.org/*' => Http::response($this->tile([232, 227, 215])),
            'wmts.terrascope.be/*' => Http::response($this->tile([61, 104, 74])),
        ]);
    }

    private function proposal(): KkprlProposal
    {
        $payload = [];
        foreach (ProposalProgress::requiredFields('bag-1') as $field) {
            $payload[$field] = KkprlPayloadFixture::value($field);
        }

        return $this->ownedProposal(['payload' => $payload]);
    }

    public function test_main_map_contains_street_tiles_and_inset_satellite_without_changing_geometry(): void
    {
        $this->fakeTiles();
        $values = ['shape_type' => 'line', 'coordinates_raw' => '[{"lat_dd":-0.88,"lng_dd":131.256},{"lat_dd":-0.88,"lng_dd":131.2564}]'];
        $before = ProposalLocationGeometry::fromValues($values);
        $map = app(KkprlLocationMapRenderer::class)->render($values);
        $this->assertSame(['detail' => 500, 'inset' => 50000], $map['scales']);
        $image = imagecreatefromstring($map['binary']);
        foreach ([[120, 150, [232, 227, 215]], [1340, 800, [61, 104, 74]]] as [$x, $y, $color]) {
            $this->assertSame($color, array_values(array_slice(imagecolorsforindex($image, imagecolorat($image, $x, $y)), 0, 3)));
        }
        imagedestroy($image);
        $after = ProposalLocationGeometry::fromValues($values);
        $this->assertSame($before->latLng, $after->latLng);
        $this->assertSame($before->measurement(), $after->measurement());
        $this->assertLessThanOrEqual(32, count(Http::recorded()));
        $count = count(Http::recorded());
        $this->assertSame($map['binary'], app(KkprlLocationMapRenderer::class)->render($values)['binary']);
        Http::assertSentCount($count);
    }

    public function test_street_tiles_use_fixed_host_wrapped_columns_application_headers_and_cache(): void
    {
        config(['app.url' => 'https://kkprl.example.test/application']);
        $binary = $this->tile([232, 227, 215]);
        Http::fake(['tile.openstreetmap.org/*' => Http::response($binary)]);
        $provider = app(KkprlOpenStreetMapTiles::class);
        $this->assertSame($binary, $provider->tile(19, -1, 12, microtime(true) + 20));
        Http::assertSent(fn ($request) => $request->url() === 'https://tile.openstreetmap.org/19/524287/12.png'
            && str_contains($request->header('User-Agent')[0], 'KKPRL-LocationMap/')
            && $request->header('Referer')[0] === 'https://kkprl.example.test/'
            && ! $request->hasHeader('Cache-Control') && ! $request->hasHeader('Pragma'));
        $this->assertSame(base64_encode($binary), Cache::get('kkprl-osm:base64-v1:19:524287:12'));
        $this->travel(6)->days();
        $this->assertSame($binary, $provider->tile(19, -1, 12, microtime(true) + 20));
        Http::assertSentCount(1);
        $this->travelBack();
    }

    public function test_osm_cache_supports_mysql_utf8_serializer_and_longer_provider_freshness(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->sqliteCreateFunction('valid_utf8', fn ($value) => mb_check_encoding($value, 'UTF-8') ? 1 : 0, 1);
        $pdo->exec('CREATE TABLE tile_cache ("key" TEXT PRIMARY KEY, value TEXT NOT NULL CHECK(valid_utf8(value)), expiration INTEGER NOT NULL)');
        $connection = new MySqlConnection($pdo, ':memory:');
        $connection->setQueryGrammar(new SQLiteGrammar($connection));
        $connection->setPostProcessor(new SQLiteProcessor);
        Cache::swap(new Repository(new DatabaseStore($connection, 'tile_cache', 'fixture-')));
        $png = $this->tile([232, 227, 215]);
        Http::fake(['tile.openstreetmap.org/*' => Http::response($png, 200, ['Cache-Control' => 'public, max-age=1209600'])]);
        $provider = app(KkprlOpenStreetMapTiles::class);
        $this->assertSame($png, $provider->tile(19, 1, 1, microtime(true) + 20));
        $this->assertTrue(mb_check_encoding($pdo->query('SELECT value FROM tile_cache')->fetchColumn(), 'UTF-8'));
        $this->travel(8)->days();
        $this->assertSame($png, $provider->tile(19, 1, 1, microtime(true) + 20));
        Http::assertSentCount(1);
        $this->travelBack();
    }

    public function test_bad_tiles_redirects_and_provider_failures_never_poison_the_cache(): void
    {
        $png = $this->tile([232, 227, 215]);
        foreach ([[substr($png, 0, 33), 200], [str_repeat('x', 524289), 200], ['', 302], ['', 503], ['<html>not a map</html>', 200]] as [$body, $status]) {
            Http::fake(['tile.openstreetmap.org/*' => Http::response($body, $status, ['Location' => 'http://127.0.0.1/'])]);
            try {
                app(KkprlOpenStreetMapTiles::class)->tile(19, 1, 1, microtime(true) + 20);
                $this->fail('Invalid street tile accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('location_map', $exception->errors());
                $this->assertNull(Cache::get('kkprl-osm:base64-v1:19:1:1'));
            }
        }
    }

    public function test_invalid_indices_and_elapsed_deadline_do_not_send_any_request(): void
    {
        foreach ([[-1, 0, 0, microtime(true) + 20], [20, 0, 0, microtime(true) + 20], [19, 0, -1, microtime(true) + 20], [19, 0, 524288, microtime(true) + 20], [19, 0, 0, microtime(true) - 1]] as $arguments) {
            try {
                app(KkprlOpenStreetMapTiles::class)->tile(...$arguments);
                $this->fail('Invalid tile operation accepted.');
            } catch (ValidationException) {
                Http::assertNothingSent();
            }
        }
    }

    public function test_street_zoom_is_bounded_and_does_not_change_viewport_or_antimeridian_geometry(): void
    {
        foreach ([['shape_type' => 'point', 'coordinates' => KkprlPayloadFixture::value('coordinates')],
            ['shape_type' => 'line', 'coordinates_raw' => '[{"lat_dd":80,"lng_dd":179.9995},{"lat_dd":80,"lng_dd":-179.9995}]'],
            ['shape_type' => 'line', 'coordinates_raw' => '[{"lat_dd":0,"lng_dd":0},{"lat_dd":0,"lng_dd":0.01}]']] as $values) {
            $geometry = ProposalLocationGeometry::fromValues($values);
            $view = ProposalLocationMapViewport::detail($geometry->points);
            $state = [$view->left, $view->top, $view->spanX, $view->spanY, $view->scaleDenominator];
            $zoom = $view->streetZoom();
            $this->assertLessThanOrEqual(19, $zoom);
            $this->assertGreaterThanOrEqual(0, $zoom);
            $size = 2 ** $zoom;
            $tiles = ((int) ceil(($view->left + $view->spanX) * $size) - (int) floor($view->left * $size))
                * ((int) ceil(($view->top + $view->spanY) * $size) - (int) floor($view->top * $size));
            $this->assertLessThanOrEqual(16, $tiles);
            $this->assertSame($state, [$view->left, $view->top, $view->spanX, $view->spanY, $view->scaleDenominator]);
        }
    }

    public function test_new_osm_snapshot_and_all_previous_layouts_survive_provider_configuration_changes(): void
    {
        $proposal = $this->proposal();
        $service = app(KkprlProposalSnapshotService::class);
        $versions = [null, '2026-10-11', '2026-10-12-map', '2026-10-12-map-inset', '2026-10-12-map-osm'];
        $snapshots = array_map(fn ($version) => $service->capture($proposal, 'bag-1', $version), $versions);
        $new = $snapshots[4];
        $this->assertSame('openstreetmap', $new->payload['location_map']['main_basemap']);
        $this->assertSame('osm-detail-inset-v2', $new->payload['location_map']['layout']);
        $this->assertSame('detail-inset-v1', $snapshots[3]->payload['location_map']['layout']);
        $this->assertNotSame($snapshots[3]->snapshotHash, $new->snapshotHash);
        config(['kkprl.location_map.main_basemap' => 'none', 'kkprl.location_map.basemap' => 'none']);
        foreach ($snapshots as $snapshot) {
            $document = new KkprlProposalDocument(['chapter' => 'bag-1', 'snapshot_payload_encrypted' => $snapshot->payload]);
            $this->assertSame($snapshot->snapshotHash, $service->captureForDocument($proposal, $document)->snapshotHash);
        }
        Http::assertNothingSent();
    }

    public function test_word_and_pdf_generation_uses_both_providers_and_preserves_private_idempotent_downloads(): void
    {
        $this->fakeTiles();
        $proposal = $this->proposal();
        $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-1');
        $this->assertCount(2, $documents);
        $zip = new ZipArchive;
        $zip->open(Storage::disk('kkprl_private')->path($documents->firstWhere('format', 'docx')->storage_path));
        $image = imagecreatefromstring($zip->getFromName('word/media/image1.png'));
        $this->assertSame([232, 227, 215], array_values(array_slice(imagecolorsforindex($image, imagecolorat($image, 120, 150)), 0, 3)));
        imagedestroy($image);
        $zip->close();
        $count = count(Http::recorded());
        $this->assertSame($documents->pluck('id')->all(), app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-1')->pluck('id')->all());
        Http::assertSentCount($count);
        config(['kkprl.location_map.main_basemap' => 'none', 'kkprl.location_map.basemap' => 'none']);
        foreach ($documents as $document) {
            $this->get(route('kkprl.proposal.document.download', $document))->assertOk();
        }
        $this->assertSame($proposal->payload, $proposal->fresh()->payload);
    }

    public function test_revocation_during_street_fetch_cannot_persist_a_document_or_map(): void
    {
        $proposal = $this->proposal();
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'tile.openstreetmap.org')) {
                $this->applicant->forceFill(['approved_at' => null])->save();
            }

            return Http::response($this->tile([232, 227, 215]));
        });
        try {
            app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-1');
            $this->fail('Revoked applicant persisted a map.');
        } catch (ProposalAccessDenied) {
            $this->assertDatabaseCount('kkprl_proposal_documents', 0);
            $this->assertSame([], Storage::disk('kkprl_private')->allFiles());
        }
    }

    public function test_street_provider_failure_is_visible_in_livewire_without_a_blank_generated_document(): void
    {
        $proposal = $this->proposal();
        app(KkprlProposalAccessService::class)->grantAccess($proposal);
        Http::fake(['tile.openstreetmap.org/*' => Http::response('unavailable', 503)]);
        Livewire::test(KkprlProposalWizard::class)->call('generateChapterDocuments', 'bag-1')
            ->assertHasErrors('location_map')->assertSee('Peta jalan belum tersedia');
        $this->assertDatabaseMissing('kkprl_proposal_documents', ['generation_status' => 'generated']);
        $this->assertSame([], Storage::disk('kkprl_private')->allFiles());
    }

    public function test_explicit_offline_mode_fetches_nothing_and_invalid_main_provider_is_rejected(): void
    {
        $values = ['shape_type' => 'point', 'coordinates' => KkprlPayloadFixture::value('coordinates')];
        config(['kkprl.location_map.main_basemap' => 'none', 'kkprl.location_map.basemap' => 'none']);
        $this->assertSame([1800, 1100], app(KkprlLocationMapRenderer::class)->render($values)['dimensions']);
        config(['kkprl.location_map.main_basemap' => 'https://127.0.0.1/private']);
        try {
            app(KkprlLocationMapRenderer::class)->render($values);
            $this->fail('Unknown main provider accepted.');
        } catch (ValidationException) {
            Http::assertNothingSent();
        }
    }
}
