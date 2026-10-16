<?php

namespace App\Services;

use App\Domain\Kkprl\ProposalDocumentPresentation;
use App\Domain\Kkprl\ProposalLocationGeometry;
use App\Domain\Kkprl\ProposalLocationMapViewport;
use Illuminate\Validation\ValidationException;

final class KkprlLocationMapRenderer
{
    private const WIDTH = ProposalLocationMapViewport::IMAGE_WIDTH;

    private const HEIGHT = ProposalLocationMapViewport::IMAGE_HEIGHT;

    private const MAP = ProposalLocationMapViewport::DETAIL_FRAME;

    public function __construct(private readonly KkprlWorldCoverTiles $tiles, private readonly KkprlOpenStreetMapTiles $streets) {}

    public function render(array $values): array
    {
        if (! function_exists('imagettftext')) {
            throw ValidationException::withMessages(['location_map' => 'Peta membutuhkan ekstensi PHP GD dengan dukungan font. Hubungi pengelola hosting.']);
        }
        $source = trim(ProposalDocumentPresentation::text($values['map_source'] ?? ''));
        if (mb_strlen($source) > 300) {
            throw ValidationException::withMessages(['location_map' => 'Sumber data lokasi maksimal 300 karakter.']);
        }
        $geometry = ProposalLocationGeometry::fromValues($values);
        $detail = ProposalLocationMapViewport::detail($geometry->points);
        $inset = ProposalLocationMapViewport::inset($detail);
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        try {
            $ink = imagecolorallocate($image, 30, 41, 59);
            $muted = imagecolorallocate($image, 71, 85, 105);
            $white = imagecolorallocate($image, 255, 255, 255);
            $accent = imagecolorallocate($image, 245, 158, 11);
            imagefill($image, 0, 0, $white);
            $this->text($image, 30, 38, 65, 'PETA LOKASI KEGIATAN', $ink, true);
            [$x, $y, $width, $height] = self::MAP;
            imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, imagecolorallocate($image, 238, 242, 246));
            $basemap = $values['basemap'] ?? config('kkprl.location_map.basemap', 'worldcover-2021');
            if (! in_array($basemap, ['worldcover-2021', 'none'], true)) {
                throw ValidationException::withMessages(['location_map' => 'Konfigurasi peta dasar tidak dikenali.']);
            }
            $mainBasemap = $values['main_basemap'] ?? (($values['layout'] ?? null) === 'detail-inset-v1'
                ? 'none' : config('kkprl.location_map.main_basemap', 'openstreetmap'));
            if (! in_array($mainBasemap, ['openstreetmap', 'none'], true)) {
                throw ValidationException::withMessages(['location_map' => 'Konfigurasi peta dasar utama tidak dikenali.']);
            }
            $deadline = microtime(true) + 20;
            if ($mainBasemap === 'openstreetmap') {
                $this->background($image, $detail, $mainBasemap, $deadline);
            }
            $this->text($image, 14, 40, 98, ($mainBasemap === 'openstreetmap' ? 'Peta utama: OpenStreetMap' : 'Detail tanpa peta dasar').' · WGS84/Web Mercator · '.($basemap === 'worldcover-2021' ? 'Inset: WorldCover 2021, sekitar 10 m; bukan citra terkini.' : 'Inset tanpa peta dasar.'), $muted);
            $this->grid($image, $detail->left, $detail->top, $detail->spanX, $detail->spanY, $muted);
            $pixels = $this->geometry($image, $geometry, $detail, $accent, 5);
            foreach ($pixels as $i => [$px, $py]) {
                imagefilledellipse($image, $px, $py, 19, 19, $white);
                imagefilledellipse($image, $px, $py, 11, 11, $accent);
                // Halo keeps point numbers distinct from grid and geometry strokes.
                $this->text($image, 18, $px + 13, $py - 8, (string) ($i + 1), $white, true);
                $this->text($image, 18, $px + 12, $py - 9, (string) ($i + 1), $ink, true);
            }
            imagerectangle($image, $x, $y, $x + $width, $y + $height, $ink);
            imagefilledrectangle($image, 1205, 145, 1265, 235, $white);
            $this->text($image, 14, 1227, 165, 'U', $ink, true);
            imagefilledpolygon($image, [1235, 180, 1222, 224, 1235, 212, 1248, 224], $ink);
            imagefilledrectangle($image, 110, 900, 430, 980, $white);
            $this->scaleBar($image, 125, 920, $detail, $ink);
            if ($mainBasemap === 'openstreetmap') {
                imagefilledrectangle($image, 450, 952, 1270, 980, $white);
                $this->text($image, 11, 460, 971, '© OpenStreetMap contributors · ODbL · https://www.openstreetmap.org/copyright', $muted);
            }
            $panel = 1330;
            imageline($image, 1310, 125, 1310, 1020, imagecolorallocate($image, 203, 213, 225));
            $cursor = 155;
            foreach ([
                'PEMOHON / INSTANSI' => $values['institution_name'] ?? $values['applicant_name'] ?? '',
                'LOKASI KEGIATAN' => implode(', ', array_filter(array_map(fn ($key) => ProposalDocumentPresentation::text($values[$key] ?? ''), ['village', 'subdistrict', 'regency', 'province']))),
            ] as $label => $value) {
                $this->text($image, 14, $panel, $cursor, $label, $muted, true);
                $this->wrapped($image, 17, $panel, $cursor + 30, mb_strimwidth(ProposalDocumentPresentation::text($value), 0, 90, '…'), 415, $ink);
                $cursor += 110;
            }
            $cursor = 385;
            $this->text($image, 14, $panel, $cursor, 'UKURAN GEOMETRI', $muted, true);
            $this->fittedText($image, 25, $panel, $cursor + 39, $geometry->measurement()['label'], 415, $ink);
            $cursor += 67;
            if ($geometry->shape !== 'point') {
                $entered = $values['water_area'] ?? null;
                $unit = $geometry->shape === 'polygon' ? 'Hektar (Ha)' : 'Kilometer (Km)';
                if (is_numeric($entered) && ($values['water_area_unit'] ?? null) === $unit) {
                    $this->text($image, 15, $panel, $cursor, 'Isian pemohon: '.number_format((float) $entered, 2, ',', '.').' '.$geometry->measurement()['unit'], $muted);
                    $cursor += 27;
                    if (round((float) $entered, 2) !== round($geometry->measurement()['value'], 2)) {
                        $cursor = $this->wrapped($image, 14, $panel, $cursor, 'Ukuran peta berbeda dari isian. Periksa koordinat dan dimensi.', 410, imagecolorallocate($image, 146, 64, 14)) + 18;
                    }
                } else {
                    $cursor = $this->wrapped($image, 14, $panel, $cursor, 'Isian dimensi belum dapat dibandingkan. Periksa angka dan satuan.', 410, $muted) + 18;
                }
            }
            $this->wrapped($image, 13, $panel, 550, 'Ukuran indikatif; bukan hasil survei batas atau pengukuran legal.', 410, $muted);
            $cursor = 615;
            $this->text($image, 14, $panel, $cursor, 'LEGENDA', $muted, true);
            imagefilledrectangle($image, $panel, $cursor + 17, $panel + 34, $cursor + 34, $accent);
            $this->text($image, 16, $panel + 47, $cursor + 34, match ($geometry->shape) {
                'polygon' => 'Area kegiatan', 'line' => 'Jalur kegiatan', default => 'Lokasi kegiatan'
            }, $ink);
            $this->text($image, 14, $panel, $cursor + 62, '1, 2, …   Nomor titik koordinat', $muted);
            $this->fittedText($image, 22, $panel, 714, 'Skala utama '.$detail->scaleLabel(), 415, $ink);
            $this->text($image, 12, $panel, 739, 'Di tengah peta · cetak ukuran asli 100%', $muted);
            $this->text($image, 14, $panel, 768, 'INSET LOKASI · '.$inset->scaleLabel(), $ink, true);
            $this->inset($image, $geometry, $detail, $inset, $basemap, $accent, $ink, $deadline);
            $this->wrapped($image, 12, $panel, 993, 'Kotak biru: cakupan peta utama.\nTanda +: pusat cakupan.', 415, $muted);
            if ($source !== '') {
                $this->wrapped($image, 11, 90, 1035, 'Sumber data lokasi: '.preg_replace('/\s+/u', ' ', $source), 1660, $muted);
            }
            if ($basemap === 'worldcover-2021') {
                $this->text($image, 11, 90, 1096, '© ESA WorldCover project 2021 · CC BY 4.0 · WMTS Terrascope · Contains modified Copernicus Sentinel data (2021) processed by ESA WorldCover consortium', $muted);
            }
            ob_start();
            try {
                imagepng($image);
                $binary = ob_get_contents();
            } finally {
                ob_end_clean();
            }

            return [
                'binary' => $binary, 'dimensions' => [self::WIDTH, self::HEIGHT], 'mime' => 'image/png',
                'print_width_points' => ProposalLocationMapViewport::PRINT_WIDTH_POINTS,
                'scales' => ['detail' => $detail->scaleDenominator, 'inset' => $inset->scaleDenominator],
            ];
        } finally {
            imagedestroy($image);
        }
    }

    private function geometry(\GdImage $image, ProposalLocationGeometry $geometry, ProposalLocationMapViewport $view, int $color, int $thickness): array
    {
        $pixels = array_map(fn ($point) => array_map(fn ($value) => (int) round($value), $view->project($point)), $geometry->points);
        if ($geometry->shape === 'polygon') {
            imagefilledpolygon($image, array_merge(...$pixels), imagecolorallocatealpha($image, 245, 158, 11, 102));
        }
        imagesetthickness($image, $thickness);
        $segments = $geometry->shape === 'point' ? 0 : count($pixels) - 1 + ($geometry->shape === 'polygon' ? 1 : 0);
        for ($i = 0; $i < $segments; $i++) {
            [$a, $b] = [$pixels[$i], $pixels[($i + 1) % count($pixels)]];
            imageline($image, $a[0], $a[1], $b[0], $b[1], $color);
        }
        imagesetthickness($image, 1);

        return $pixels;
    }

    private function inset(\GdImage $image, ProposalLocationGeometry $geometry, ProposalLocationMapViewport $detail, ProposalLocationMapViewport $view, string $basemap, int $accent, int $ink, float $deadline): void
    {
        [$x, $y, $width, $height] = $view->frame;
        imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, imagecolorallocate($image, 238, 242, 246));
        if ($basemap === 'worldcover-2021') {
            $this->background($image, $view, $basemap, $deadline);
        }
        $this->geometry($image, $geometry, $view, $accent, 2);
        [$left, $top] = array_map(fn ($value) => (int) round($value), $view->project([$detail->left, $detail->top]));
        [$right, $bottom] = array_map(fn ($value) => (int) round($value), $view->project([$detail->left + $detail->spanX, $detail->top + $detail->spanY]));
        imagerectangle($image, $left, $top, $right, $bottom, imagecolorallocate($image, 56, 189, 248));
        // A locator symbol identifies the centre; it is not an enlarged application boundary.
        [$cx, $cy] = array_map(fn ($value) => (int) round($value), $view->project([$detail->left + $detail->spanX / 2, $detail->top + $detail->spanY / 2]));
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledellipse($image, $cx, $cy, 9, 9, $white);
        imageline($image, $cx - 6, $cy, $cx + 6, $cy, $accent);
        imageline($image, $cx, $cy - 6, $cx, $cy + 6, $accent);
        imagerectangle($image, $x, $y, $x + $width, $y + $height, $ink);
    }

    private function background(\GdImage $image, ProposalLocationMapViewport $view, string $provider, float $deadline): void
    {
        [$left, $top, $spanX, $spanY, $zoom] = [$view->left, $view->top, $view->spanX, $view->spanY, $view->zoom];
        if ($provider === 'openstreetmap') {
            $zoom = $view->streetZoom();
        }
        $n = 2 ** $zoom;
        $minX = (int) floor($left * $n);
        $maxX = (int) ceil(($left + $spanX) * $n) - 1;
        $minY = (int) floor($top * $n);
        $maxY = (int) ceil(($top + $spanY) * $n) - 1;
        if (($maxX - $minX + 1) * ($maxY - $minY + 1) > 16) {
            throw ValidationException::withMessages(['location_map' => 'Cakupan peta melebihi batas tile. Periksa koordinat.']);
        }
        $mosaic = imagecreatetruecolor(($maxX - $minX + 1) * 256, ($maxY - $minY + 1) * 256);
        try {
            for ($row = $minY; $row <= $maxY; $row++) {
                for ($column = $minX; $column <= $maxX; $column++) {
                    $binary = $provider === 'openstreetmap'
                        ? $this->streets->tile($zoom, $column, $row, $deadline)
                        : $this->tiles->tile($zoom, $column, $row, $deadline);
                    $tile = @imagecreatefromstring($binary);
                    if ($tile === false) {
                        throw ValidationException::withMessages(['location_map' => 'Tile citra tidak dapat dibaca. Coba generate kembali.']);
                    }
                    imagecopy($mosaic, $tile, ($column - $minX) * 256, ($row - $minY) * 256, 0, 0, 256, 256);
                    imagedestroy($tile);
                }
            }
            [$x, $y, $width, $height] = $view->frame;
            imagecopyresampled($image, $mosaic, $x, $y, (int) round(($left * $n - $minX) * 256), (int) round(($top * $n - $minY) * 256), $width, $height, max(1, (int) round($spanX * $n * 256)), max(1, (int) round($spanY * $n * 256)));
        } finally {
            imagedestroy($mosaic);
        }
    }

    private function grid(\GdImage $image, float $left, float $top, float $spanX, float $spanY, int $color): void
    {
        [$x, $y, $width, $height] = self::MAP;
        $grid = imagecolorallocatealpha($image, 255, 255, 255, 78);
        for ($i = 0; $i <= 4; $i++) {
            $px = (int) round($x + $i * $width / 4);
            $py = (int) round($y + $i * $height / 4);
            imageline($image, $px, $y, $px, $y + $height, $grid);
            imageline($image, $x, $py, $x + $width, $py, $grid);
            $lng = fmod(($left + $spanX * $i / 4) * 360 + 360, 360) - 180;
            $lat = rad2deg(atan(sinh(M_PI * (1 - 2 * ($top + $spanY * $i / 4)))));
            $this->text($image, 12, max(40, min(1200, $px - 42)), $y + $height + 25, number_format(abs($lng), 4, '.', '').'° '.($lng < 0 ? 'B' : 'T'), $color);
            $this->text($image, 10, 4, $py + 4, number_format(abs($lat), 4, '.', '').'° '.($lat < 0 ? 'S' : 'U'), $color);
        }
    }

    private function scaleBar(\GdImage $image, int $x, int $y, ProposalLocationMapViewport $view, int $color): void
    {
        $metresPerPixel = $view->metresPerPixel();
        $target = $metresPerPixel * 210;
        $power = 10 ** floor(log10($target));
        $metres = max($power, floor($target / $power) * $power);
        $length = (int) round($metres / $metresPerPixel);
        imagefilledrectangle($image, $x, $y, $x + $length, $y + 8, $color);
        $this->text($image, 12, $x, $y + 29, '0', $color);
        $this->text($image, 12, $x + $length - 30, $y + 29, $metres >= 1000 ? number_format($metres / 1000, 1, ',', '.').' km' : (int) $metres.' m', $color);
        $this->text($image, 12, $x, $y + 53, 'Skala grafis pada tengah peta', $color);
    }

    private function text(\GdImage $image, int $size, int $x, int $y, string $text, int $color, bool $bold = false): void
    {
        imagettftext($image, $size, 0, $x, $y, $color, $this->font($bold), $text);
    }

    private function fittedText(\GdImage $image, int $size, int $x, int $y, string $text, int $width, int $color): void
    {
        do {
            $box = imagettfbbox($size, 0, $this->font(true), $text);
            if ($width >= $box[2] - $box[0] || $size <= 12) {
                break;
            }
            $size--;
        } while (true);
        $this->text($image, $size, $x, $y, $text, $color, true);
    }

    private function font(bool $bold = false): string
    {
        return base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans'.($bold ? '-Bold' : '').'.ttf');
    }

    private function wrapped(\GdImage $image, int $size, int $x, int $y, string $text, int $width, int $color): int
    {
        foreach (explode("\n", str_replace('\\n', "\n", $text)) as $paragraph) {
            $line = '';
            foreach (preg_split('/\s+/u', $paragraph) ?: [] as $word) {
                $candidate = $line === '' ? $word : $line.' '.$word;
                $box = imagettfbbox($size, 0, $this->font(), $candidate);
                if ($width >= $box[2] - $box[0]) {
                    $line = $candidate;

                    continue;
                }
                $wordBox = imagettfbbox($size, 0, $this->font(), $word);
                if ($line !== '' && $width >= $wordBox[2] - $wordBox[0]) {
                    $this->text($image, $size, $x, $y, $line, $color);
                    $y += $size + 8;
                    $line = '';
                } elseif ($line !== '') {
                    // Fill the current line before splitting a token wider than the box.
                    $line .= ' ';
                }
                foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $character) {
                    $box = imagettfbbox($size, 0, $this->font(), $line.$character);
                    if ($width < $box[2] - $box[0] && $line !== '') {
                        $this->text($image, $size, $x, $y, $line, $color);
                        $y += $size + 8;
                        $line = '';
                    }
                    $line .= $character;
                }
            }
            $this->text($image, $size, $x, $y, $line, $color);
            $y += $size + 8;
        }

        return $y;
    }
}
