<?php

namespace App\Services;

use App\Domain\Kkprl\InvalidBathymetry;

class KkprlBathymetryMapRenderer
{
    public function png(array $result, array $points, string $shape): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new InvalidBathymetry('Pembuatan peta memerlukan GD pada PHP hosting. Gunakan unggah gambar sementara.');
        }
        $grid = $result['grid'];
        $summary = $result['summary'];
        $bounds = $grid['bounds'];
        $image = imagecreatetruecolor(1100, 900);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 30, 41, 59);
        $red = imagecolorallocate($image, 190, 18, 60);
        imagefill($image, 0, 0, $white);
        imagestring($image, 5, 55, 25, 'Batimetri sekunder GMRT - sekitar lokasi usaha', $ink);
        imagestring($image, 3, 55, 52, 'Koordinat WGS84 | elevasi (m) | area tambahan '.$summary['buffer_km'].' km', $ink);
        $ratio = ($bounds['east'] - $bounds['west']) * cos(deg2rad(($bounds['north'] + $bounds['south']) / 2)) / ($bounds['north'] - $bounds['south']);
        $width = min(780, 600 * $ratio);
        $height = $width / $ratio;
        $left = 85 + (780 - $width) / 2;
        $top = 115 + (600 - $height) / 2;
        $dx = $width / $grid['ncols'];
        $dy = $height / $grid['nrows'];
        $minimum = $summary['minimum_elevation_m'];
        $maximum = $summary['maximum_elevation_m'];
        foreach ($grid['values'] as $row => $cells) {
            foreach ($cells as $column => $value) {
                if ($value !== null) {
                    imagefilledrectangle($image, (int) floor($left + $column * $dx), (int) floor($top + $row * $dy),
                        (int) ceil($left + ($column + 1) * $dx) - 1, (int) ceil($top + ($row + 1) * $dy) - 1,
                        $this->colour($image, $value, $minimum, $maximum));
                }
            }
        }
        $levels = $this->levels($minimum, $maximum);
        foreach ($levels as $level) {
            $labelled = false;
            for ($row = 0; $row < $grid['nrows'] - 1; $row++) {
                for ($column = 0; $column < $grid['ncols'] - 1; $column++) {
                    $vertices = [[$column + 0.5, $row + 0.5, $grid['values'][$row][$column]],
                        [$column + 1.5, $row + 0.5, $grid['values'][$row][$column + 1]],
                        [$column + 1.5, $row + 1.5, $grid['values'][$row + 1][$column + 1]],
                        [$column + 0.5, $row + 1.5, $grid['values'][$row + 1][$column]]];
                    if (in_array(null, array_column($vertices, 2), true)) {
                        continue;
                    }
                    foreach ([[$vertices[0], $vertices[1], $vertices[2]], [$vertices[0], $vertices[2], $vertices[3]]] as $triangle) {
                        $segment = $this->contourSegment($triangle, $level);
                        if ($segment === null) {
                            continue;
                        }
                        [$a, $b] = $segment;
                        imageline($image, (int) round($left + $a[0] * $dx), (int) round($top + $a[1] * $dy),
                            (int) round($left + $b[0] * $dx), (int) round($top + $b[1] * $dy), $ink);
                        if (! $labelled) {
                            $x = (int) round($left + $a[0] * $dx);
                            $y = (int) round($top + $a[1] * $dy);
                            $text = (string) $level;
                            imagefilledrectangle($image, $x, $y, $x + strlen($text) * 6, $y + 12, $white);
                            imagestring($image, 2, $x, $y, $text, $ink);
                            $labelled = true;
                        }
                    }
                }
            }
        }
        imagerectangle($image, (int) $left, (int) $top, (int) round($left + $width), (int) round($top + $height), $ink);
        for ($i = 0; $i <= 4; $i++) {
            $x = $left + $width * $i / 4;
            $y = $top + $height * $i / 4;
            imagestring($image, 2, (int) round($x - 25), (int) round($top + $height + 10), number_format($bounds['west'] + ($bounds['east'] - $bounds['west']) * $i / 4, 4, '.', ''), $ink);
            imagestring($image, 2, 5, (int) round($y - 5), number_format($bounds['north'] - ($bounds['north'] - $bounds['south']) * $i / 4, 4, '.', ''), $ink);
        }
        $positions = [];
        foreach ($points as [$lat, $lng]) {
            $positions[] = [$left + ($lng - $bounds['west']) / ($bounds['east'] - $bounds['west']) * $width,
                $top + ($bounds['north'] - $lat) / ($bounds['north'] - $bounds['south']) * $height];
        }
        imagesetthickness($image, 2);
        if (in_array($shape, ['line', 'polygon'], true)) {
            for ($i = 1; $i < count($positions); $i++) {
                imageline($image, (int) round($positions[$i - 1][0]), (int) round($positions[$i - 1][1]), (int) round($positions[$i][0]), (int) round($positions[$i][1]), $red);
            }
            if ($shape === 'polygon' && count($positions) >= 3) {
                imageline($image, (int) round($positions[0][0]), (int) round($positions[0][1]), (int) round($positions[array_key_last($positions)][0]), (int) round($positions[array_key_last($positions)][1]), $red);
            }
        }
        imagesetthickness($image, 1);
        foreach ($positions as $i => [$x, $y]) {
            imagefilledellipse($image, (int) round($x), (int) round($y), 9, 9, $red);
            if ($i < 20) {
                imagestring($image, 3, (int) round($x + 7), (int) round($y - 7), (string) ($i + 1), $red);
            }
        }
        imagestring($image, 3, 900, 90, 'Elevasi (m)', $ink);
        for ($i = 0; $i < 240; $i++) {
            $value = $maximum - ($maximum - $minimum) * $i / 239;
            imageline($image, 900, 120 + $i, 925, 120 + $i, $this->colour($image, $value, $minimum, $maximum));
        }
        for ($i = 0; $i <= 4; $i++) {
            imagestring($image, 3, 935, 115 + $i * 60, number_format($maximum - ($maximum - $minimum) * $i / 4, 1, '.', ''), $ink);
        }
        imagerectangle($image, 900, 385, 920, 400, $ink);
        imagestring($image, 2, 930, 385, 'Tanpa data', $ink);
        imagestring($image, 2, 900, 425, 'Merah: titik Bab 1', $red);
        imagestring($image, 3, 940, 470, 'N', $ink);
        imageline($image, 944, 495, 944, 540, $ink);
        imageline($image, 944, 495, 936, 510, $ink);
        imageline($image, 944, 495, 952, 510, $ink);
        $metres = ($bounds['east'] - $bounds['west']) * 111320 * cos(deg2rad(($bounds['north'] + $bounds['south']) / 2));
        $scale = $this->niceStep($metres / 5);
        $scalePixels = $width * $scale / $metres;
        imageline($image, 90, 755, (int) round(90 + $scalePixels), 755, $ink);
        imageline($image, 90, 750, 90, 760, $ink);
        imageline($image, (int) round(90 + $scalePixels), 750, (int) round(90 + $scalePixels), 760, $ink);
        imagestring($image, 3, 90, 765, $scale.' m (perkiraan)', $ink);
        imagestring($image, 2, 55, 805, 'Kontur interpolasi antar pusat sel valid; putih = tanpa data. Nilai negatif bukan kedalaman muka air saat ini.', $ink);
        imagestring($image, 2, 55, 825, 'Sumber: GMRT / Ryan et al. (2009), doi:10.1029/2008GC002332 | CC BY 4.0 | diolah aplikasi', $ink);
        imagestring($image, 2, 55, 845, 'Data: doi:10.1594/IEDA.100001 | Diakses '.substr($summary['retrieved_at'], 0, 10).' | Bukan untuk navigasi.', $ink);
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    // Linear crossings on triangles avoid ambiguous saddle cells. Coordinates are cell centres.
    public function contourSegment(array $triangle, float $level): ?array
    {
        $crossings = [];
        for ($i = 0; $i < 3; $i++) {
            $a = $triangle[$i];
            $b = $triangle[($i + 1) % 3];
            if ($a[2] === null || $b[2] === null) {
                return null;
            }
            if (($a[2] < $level && $b[2] >= $level) || ($b[2] < $level && $a[2] >= $level)) {
                $fraction = ($level - $a[2]) / ($b[2] - $a[2]);
                $crossings[] = [$a[0] + $fraction * ($b[0] - $a[0]), $a[1] + $fraction * ($b[1] - $a[1])];
            }
        }

        return count($crossings) === 2 ? $crossings : null;
    }

    private function levels(float $minimum, float $maximum): array
    {
        if ($maximum <= $minimum) {
            return [];
        }
        $step = $this->niceStep(($maximum - $minimum) / 6);
        $levels = [];
        for ($value = ceil($minimum / $step) * $step; $value < $maximum && count($levels) < 10; $value += $step) {
            if ($value > $minimum) {
                $levels[] = $value;
            }
        }

        return $levels;
    }

    private function niceStep(float $value): float
    {
        $power = 10 ** floor(log10(max($value, 0.000001)));
        $scaled = $value / $power;

        return ($scaled >= 5 ? 5 : ($scaled >= 2 ? 2 : 1)) * $power;
    }

    private function colour($image, float $value, float $minimum, float $maximum): int
    {
        if ($value >= 0) {
            return imagecolorallocate($image, 105, 170, 95);
        }
        $fraction = max(0, min(1, ($value - $minimum) / max(0.000001, min(0, $maximum) - $minimum)));

        return imagecolorallocate($image, (int) round(20 + 170 * $fraction), (int) round(65 + 160 * $fraction), (int) round(125 + 115 * $fraction));
    }
}
