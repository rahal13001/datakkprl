<?php

namespace App\Services;

use App\Domain\Kkprl\InvalidBathymetry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class KkprlBathymetryService
{
    public const ENDPOINT = 'https://www.gmrt.org/services/GridServer';

    public function fetch(array $points, float $bufferKm): array
    {
        app(KkprlProposalAccessService::class)->assertApproved();
        if (! in_array($bufferKm, [0.5, 1.0, 2.0, 5.0], true) || ! array_is_list($points) || count($points) < 1 || count($points) > 1000) {
            throw new InvalidBathymetry('Lengkapi koordinat Bab 1 dan pilih jarak tambahan yang tersedia.');
        }
        foreach ($points as $point) {
            if (! is_array($point) || ! array_is_list($point) || count($point) !== 2 || ! is_numeric($point[0]) || ! is_numeric($point[1])
                || ! is_finite((float) $point[0]) || ! is_finite((float) $point[1]) || abs($point[0]) > 85 || abs($point[1]) > 180) {
                throw new InvalidBathymetry('Koordinat Bab 1 belum valid. Periksa semua titik lokasi.');
            }
        }
        $latitudes = array_column($points, 0);
        $longitudes = array_column($points, 1);
        $middle = (min($latitudes) + max($latitudes)) / 2;
        $metresPerLongitude = 111320 * cos(deg2rad($middle));
        $bounds = ['west' => min($longitudes) - $bufferKm * 1000 / $metresPerLongitude,
            'east' => max($longitudes) + $bufferKm * 1000 / $metresPerLongitude,
            'south' => min($latitudes) - $bufferKm * 1000 / 111320,
            'north' => max($latitudes) + $bufferKm * 1000 / 111320];
        $width = ($bounds['east'] - $bounds['west']) * $metresPerLongitude;
        $height = ($bounds['north'] - $bounds['south']) * 111320;
        if ($bounds['west'] < -180 || $bounds['east'] > 180 || $bounds['south'] < -85 || $bounds['north'] > 85 || max($width, $height) > 50000) {
            throw new InvalidBathymetry('Area peta terlalu luas atau melewati batas koordinat. Batasi area sampai 50 km per sisi; lintasan melintasi bujur 180° belum didukung.');
        }
        $request = $bounds + ['format' => 'esriascii', 'layer' => 'topo', 'mresolution' => max(100, ceil(max($width, $height) / 128 / 25) * 25)];
        try {
            $response = Http::connectTimeout(5)->timeout(20)->withOptions(['allow_redirects' => false, 'progress' => function ($total, $received): void {
                if ($total > 4194304 || $received > 4194304) {
                    throw new InvalidBathymetry('Grid GMRT terlalu besar. Pilih area lebih kecil.');
                }
            }])->get(self::ENDPOINT, $request);
        } catch (ConnectionException $exception) {
            throw new InvalidBathymetry('GMRT belum dapat dihubungi. Coba lagi; peta dan narasi sebelumnya tetap tersedia.', previous: $exception);
        }
        if (! $response->successful()) {
            throw new InvalidBathymetry('GMRT belum menyediakan data saat ini. Coba lagi; peta dan narasi sebelumnya tetap tersedia.');
        }
        $stream = $response->toPsrResponse()->getBody();
        try {
            $body = '';
            while (! $stream->eof() && strlen($body) <= 4194304) {
                $chunk = $stream->read(min(65536, 4194305 - strlen($body)));
                if ($chunk === '') {
                    break;
                }
                $body .= $chunk;
            }
        } catch (\RuntimeException $exception) {
            throw new InvalidBathymetry('Data GMRT belum dapat dibaca. Coba lagi; peta sebelumnya tetap tersedia.', previous: $exception);
        } finally {
            $stream->close();
        }
        if (strlen($body) > 4194304) {
            throw new InvalidBathymetry('Grid GMRT terlalu besar. Pilih area lebih kecil.');
        }
        $grid = $this->parse($body, $bounds);
        $values = array_values(array_filter(array_merge(...$grid['values']), fn ($value) => $value !== null));
        if ($values === []) {
            throw new InvalidBathymetry('GMRT tidak memiliki nilai grid pada area ini. Gunakan sumber atau gambar lain.');
        }
        $summary = ['method' => 'gmrt-bathymetry-v1', 'endpoint' => self::ENDPOINT, 'request' => $request,
            'requested_bounds' => $bounds, 'grid_bounds' => $grid['bounds'], 'ncols' => $grid['ncols'], 'nrows' => $grid['nrows'],
            'cellsize_degrees' => $grid['cellsize'], 'output_spacing_east_m' => $grid['cellsize'] * $metresPerLongitude,
            'output_spacing_north_m' => $grid['cellsize'] * 111320, 'valid_cells' => count($values),
            'missing_cells' => $grid['ncols'] * $grid['nrows'] - count($values),
            'nonnegative_cells' => count(array_filter($values, fn ($value) => $value >= 0)),
            'minimum_elevation_m' => min($values), 'maximum_elevation_m' => max($values),
            'retrieved_at' => now()->toIso8601String(), 'response_sha256' => hash('sha256', $body),
            'original_resolution' => 'unknown', 'vertical_datum' => 'unknown', 'buffer_km' => $bufferKm];

        return ['grid' => $grid, 'summary' => $summary, 'archive' => base64_encode(gzencode($body))];
    }

    private function parse(string $body, array $requested): array
    {
        $lines = preg_split('/\R/', trim($body), 519);
        $header = [];
        foreach (array_splice($lines, 0, 6) as $line) {
            $parts = preg_split('/\s+/', trim($line), 3);
            if (count($parts) !== 2 || ! is_numeric($parts[1]) || ! is_finite((float) $parts[1]) || isset($header[strtolower($parts[0])])) {
                throw new InvalidBathymetry('Header grid GMRT tidak valid. Coba lagi atau gunakan gambar lain.');
            }
            $header[strtolower($parts[0])] = (float) $parts[1];
        }
        if (array_diff(['ncols', 'nrows', 'xllcorner', 'yllcorner', 'cellsize', 'nodata_value'], array_keys($header)) !== []
            || $header['ncols'] < 2 || $header['nrows'] < 2 || $header['ncols'] > 512 || $header['nrows'] > 512
            || floor($header['ncols']) !== $header['ncols'] || floor($header['nrows']) !== $header['nrows']
            || $header['ncols'] * $header['nrows'] > 65536 || $header['cellsize'] <= 0) {
            throw new InvalidBathymetry('Ukuran atau format grid GMRT tidak didukung. Pilih area lebih kecil.');
        }
        $bounds = ['west' => $header['xllcorner'], 'east' => $header['xllcorner'] + $header['ncols'] * $header['cellsize'],
            'south' => $header['yllcorner'], 'north' => $header['yllcorner'] + $header['nrows'] * $header['cellsize']];
        foreach ($bounds as $side => $value) {
            if (abs($value - $requested[$side]) > 2 * $header['cellsize'] || abs($value) > (in_array($side, ['west', 'east']) ? 180 : 85)) {
                throw new InvalidBathymetry('Area grid GMRT tidak sesuai koordinat Bab 1. Coba kembali.');
            }
        }
        if ($bounds['west'] > $requested['west'] + $header['cellsize'] || $bounds['east'] < $requested['east'] - $header['cellsize']
            || $bounds['south'] > $requested['south'] + $header['cellsize'] || $bounds['north'] < $requested['north'] - $header['cellsize']) {
            throw new InvalidBathymetry('Cakupan grid GMRT tidak sesuai area yang diminta.');
        }
        if (count($lines) !== (int) $header['nrows']) {
            throw new InvalidBathymetry('Jumlah baris grid GMRT tidak sesuai header.');
        }
        $rows = [];
        foreach ($lines as $line) {
            $cells = preg_split('/\s+/', trim($line), (int) $header['ncols'] + 1);
            if (count($cells) !== (int) $header['ncols']) {
                throw new InvalidBathymetry('Jumlah kolom grid GMRT tidak sesuai header.');
            }
            $row = [];
            foreach ($cells as $cell) {
                if (! is_numeric($cell) || ! is_finite((float) $cell)) {
                    throw new InvalidBathymetry('Grid GMRT memuat angka yang tidak valid.');
                }
                $value = (float) $cell;
                if ($value === $header['nodata_value']) {
                    $row[] = null;
                } elseif ($value < -12000 || $value > 9000) {
                    throw new InvalidBathymetry('Elevasi grid GMRT berada di luar rentang yang dapat diterima.');
                } else {
                    $row[] = $value;
                }
            }
            $rows[] = $row;
        }

        return ['ncols' => (int) $header['ncols'], 'nrows' => (int) $header['nrows'], 'cellsize' => $header['cellsize'], 'bounds' => $bounds, 'values' => $rows];
    }

    public function report(array $summary): string
    {
        $number = fn ($value) => number_format($value, 1, ',', '.');

        return 'Peta batimetri sekunder diolah dari grid GMRT pada area sekitar koordinat Bab 1 dengan jarak tambahan '.$number($summary['buffer_km']).' km. '
            .'Rentang elevasi pada seluruh area peta adalah '.$number($summary['minimum_elevation_m']).' m hingga '.$number($summary['maximum_elevation_m']).' m. '
            .'Rentang ini tidak menyatakan kedalaman di titik usaha atau hanya di dalam batas permohonan. '
            .'Terdapat '.$summary['valid_cells'].' sel valid, '.$summary['missing_cells'].' sel tanpa data, dan '.$summary['nonnegative_cells'].' sel dengan elevasi nol/positif yang dapat berada di daratan. '
            .'Jarak antar sel keluaran sekitar '.$number($summary['output_spacing_east_m']).' m timur–barat dan '.$number($summary['output_spacing_north_m']).' m utara–selatan; ini bukan ketelitian atau resolusi asli pengukuran. '
            .'Kontur diinterpolasi antar pusat sel valid; area tanpa data tidak diisi. GMRT menggabungkan sumber dengan resolusi beragam dan isian GEBCO. '
            .'Datum vertikal, resolusi asli setempat dan periode pengukuran belum diketahui. Elevasi negatif bukan kedalaman terhadap muka air saat ini. '
            .'Peta tidak membuktikan jenis sedimen dan perlu verifikasi setempat; bukan untuk navigasi.';
    }
}
