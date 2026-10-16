<?php

// Synthetic public-coordinate preview only. No database writes or applicant data.
use App\Domain\Kkprl\ProposalDocumentPresentation;
use App\Domain\Kkprl\ProposalDocumentSectionCatalog;
use App\Domain\Kkprl\ProposalFieldCatalog;
use App\Domain\Kkprl\ProposalProgress;
use App\Services\KkprlLocationMapRenderer;
use App\Services\KkprlProposalDocxRenderer;
use App\Services\KkprlProposalPdfRenderer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\KkprlPayloadFixture;

require __DIR__.'/../vendor/autoload.php';
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('CACHE_STORE=array');
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
DB::connection()->beforeExecuting(function (): never {
    throw new RuntimeException('Database access is forbidden for map preview.');
});
$live = in_array('--live', $argv, true);
$osm = in_array('--osm', $argv, true);
$streetOnly = in_array('--street-only', $argv, true);
$longSource = in_array('--long-source', $argv, true);
$smallSite = in_array('--small-site', $argv, true);
$antimeridian = in_array('--antimeridian', $argv, true);
$highLatitude = in_array('--high-latitude', $argv, true);
config(['kkprl.location_map.basemap' => $live && ! $streetOnly ? 'worldcover-2021' : 'none']);
config(['kkprl.location_map.main_basemap' => $osm ? 'openstreetmap' : 'none']);
if ($live && $osm) {
    // Public tiles only, isolated from application cache and preserved across preview retries.
    config(['cache.default' => 'file', 'cache.stores.file.path' => storage_path('framework/testing/osm-preview-cache')]);
}
if (! $live) {
    Http::preventStrayRequests();
    if ($osm) {
        // Synthetic street-like tiles for reproducible export checks without provider traffic.
        $tile = imagecreatetruecolor(256, 256);
        imagefill($tile, 0, 0, imagecolorallocate($tile, 232, 227, 215));
        imagesetthickness($tile, 9);
        imageline($tile, 0, 128, 255, 128, imagecolorallocate($tile, 255, 255, 255));
        imageline($tile, 128, 0, 128, 255, imagecolorallocate($tile, 255, 255, 255));
        ob_start();
        imagepng($tile);
        $png = ob_get_clean();
        imagedestroy($tile);
        Http::fake(['tile.openstreetmap.org/*' => Http::response($png)]);
    }
}
$output = base_path('docs/export_sample/peta/QA');
if (! is_dir($output) && ! mkdir($output, 0700, true)) {
    throw new RuntimeException('Cannot create synthetic preview directory.');
}
foreach (['polygon', 'line', 'point'] as $shape) {
    $values = [];
    foreach (ProposalProgress::requiredFields('bag-1') as $field) {
        $values[$field] = KkprlPayloadFixture::value($field);
    }
    $points = [
        ['lat_dd' => -.8800, 'lng_dd' => 131.2560],
        ['lat_dd' => -.8800, 'lng_dd' => 131.2590],
        ['lat_dd' => -.8830, 'lng_dd' => 131.2590],
        ['lat_dd' => -.8830, 'lng_dd' => 131.2560],
    ];
    if ($smallSite) {
        // About 44.5 × 44.5 m: detail should use 1:1,000, not enlarge the application boundary.
        $points = [
            ['lat_dd' => -.8800, 'lng_dd' => 131.2560],
            ['lat_dd' => -.8800, 'lng_dd' => 131.2564],
            ['lat_dd' => -.8804, 'lng_dd' => 131.2564],
            ['lat_dd' => -.8804, 'lng_dd' => 131.2560],
        ];
    }
    if ($antimeridian || $highLatitude) {
        $latitude = $highLatitude ? 80.0 : 0.0;
        $west = $antimeridian ? 179.9995 : 1.0;
        $east = $antimeridian ? -179.9995 : 1.001;
        $points = [
            ['lat_dd' => $latitude, 'lng_dd' => $west], ['lat_dd' => $latitude, 'lng_dd' => $east],
            ['lat_dd' => $latitude + .001, 'lng_dd' => $east], ['lat_dd' => $latitude + .001, 'lng_dd' => $west],
        ];
    }
    if ($shape !== 'polygon') {
        $points = array_slice($points, 0, $shape === 'point' ? 1 : 2);
    }
    $values = array_replace($values, [
        'institution_name' => 'Pemohon Contoh — Fixture Sintetis',
        'applicant_name' => 'Pemohon Contoh', 'main_activity' => 'Kegiatan contoh',
        'village' => 'Lokasi contoh', 'subdistrict' => 'Kecamatan contoh',
        'regency' => $antimeridian || $highLatitude ? 'Koordinat demonstrasi' : 'Sorong',
        'province' => $antimeridian || $highLatitude ? 'Fixture sintetis' : 'Papua Barat Daya',
        'shape_type' => $shape, 'water_area' => $smallSite ? ($shape === 'line' ? '.04' : '.20') : ($shape === 'line' ? '.33' : '11.13'),
        'water_area_unit' => $shape === 'line' ? 'Kilometer (Km)' : 'Hektar (Ha)',
        'coordinates_raw' => json_encode($points, JSON_THROW_ON_ERROR),
        'map_source' => $longSource ? str_repeat('W', 300) : 'Koordinat sintetis untuk demonstrasi; bukan lokasi permohonan.',
        'site_plan_description' => '<p>Rencana tapak contoh untuk memeriksa tata letak ekspor. Semua identitas dalam dokumen ini merupakan fixture sintetis.</p>',
    ]);
    $values['coordinates'] = implode("\n", array_map(fn ($point, $i) => 'Titik '.($i + 1).': Latitude DD ('.$point['lat_dd'].'), Longitude DD ('.$point['lng_dd'].')', $points, array_keys($points)));
    $map = app(KkprlLocationMapRenderer::class)->render($values);
    unset($values['coordinates_raw'], $values['map_source']);
    $data = [
        'chapter' => 'bag-1', 'chapterTitle' => 'RENCANA BANGUNAN DAN INSTALASI LAUT',
        'coverName' => $values['institution_name'], 'ticket' => 'CONTOH-PETA', 'year' => 2026,
        'values' => $values, 'fieldLabels' => [], 'sectionLabels' => [],
        'inlineAttachments' => [], 'appendixAttachments' => [], 'landscape' => false, 'locationMap' => $map,
    ];
    foreach (array_keys($values) as $field) {
        $data['fieldLabels'][$field] = ProposalFieldCatalog::label($field);
        if ($heading = ProposalDocumentSectionCatalog::heading('bag-1', $field)) {
            $data['sectionLabels'][$field] = $heading;
        }
    }
    $data['blocks'] = ProposalDocumentPresentation::blocks($data);
    $name = ($osm ? 'osm-'.($live ? 'live-' : 'fake-') : 'inset-'.($live ? 'satelit-' : 'grid-')).$shape.($streetOnly ? '-street-only' : '').($smallSite ? '-small-site' : '').($antimeridian ? '-antimeridian' : '').($highLatitude ? '-high-latitude' : '').($longSource ? '-long-source' : '');
    file_put_contents($output.'/'.$name.'.png', $map['binary']);
    file_put_contents($output.'/'.$name.'.docx', app(KkprlProposalDocxRenderer::class)->render($data));
    file_put_contents($output.'/'.$name.'.pdf', app(KkprlProposalPdfRenderer::class)->render($data));
    echo $name.': PNG, DOCX, PDF; detail 1:'.$map['scales']['detail'].', inset 1:'.$map['scales']['inset'].'; peak memory '.round(memory_get_peak_usage(true) / 1048576, 1)." MiB\n";
}
