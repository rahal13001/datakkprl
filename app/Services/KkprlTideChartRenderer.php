<?php

namespace App\Services;

use App\Domain\Kkprl\InvalidTideData;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Str;

final class KkprlTideChartRenderer
{
    public const METHOD = 'tide_time_series_png_v1';

    public const LEFT = 140;

    public const TOP = 185;

    public const PLOT_WIDTH = 1300;

    public const PLOT_HEIGHT = 420;

    /** Draw every validated sample, without resampling or fitting a model. */
    public function build(array $table, array $options, array $source): array
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new InvalidTideData('Grafik memerlukan ekstensi GD PHP seperti peta batimetri. Hubungi penyedia hosting untuk mengaktifkannya.');
        }
        $series = (new KkprlTideDataAnalyzer)->series($table, $options);
        $points = $series['points'];
        $summary = $series['summary'];
        $times = array_keys($points);
        $intervals = [];
        for ($i = 1; $i < count($times); $i++) {
            $intervals[] = $times[$i] - $times[$i - 1];
        }
        sort($intervals, SORT_NUMERIC);
        $middle = intdiv(count($intervals), 2);
        $median = count($intervals) % 2 === 0 ? ($intervals[$middle - 1] + $intervals[$middle]) / 2 : (float) $intervals[$middle];
        $gapThreshold = 1.5 * $median;
        $padding = max(($summary['maximum_m'] - $summary['minimum_m']) * 0.08, 0.01);
        $rawStep = ($summary['maximum_m'] - $summary['minimum_m'] + 2 * $padding) / 5;
        $power = 10 ** floor(log10($rawStep));
        $step = $power * (collect([1, 2, 5, 10])->first(fn ($v) => $v * $power >= $rawStep));
        $yMin = floor(($summary['minimum_m'] - $padding) / $step) * $step;
        $yMax = ceil(($summary['maximum_m'] + $padding) / $step) * $step;
        $start = $times[0];
        $end = $times[array_key_last($times)];
        $x = fn (int $time): int => (int) round(self::LEFT + ($time - $start) / ($end - $start) * self::PLOT_WIDTH);
        $y = fn (float $height): int => (int) round(self::TOP + ($yMax - $height) / ($yMax - $yMin) * self::PLOT_HEIGHT);
        $zone = new DateTimeZone($summary['timezone']);
        $timezoneLabel = ['UTC' => 'UTC', 'Asia/Jakarta' => 'WIB (UTC+07)', 'Asia/Makassar' => 'WITA (UTC+08)', 'Asia/Jayapura' => 'WIT (UTC+09)'][$summary['timezone']];
        $image = imagecreatetruecolor(1600, 1080);
        if ($image === false) {
            throw new InvalidTideData('Grafik belum dapat dibuat. Data dan narasi sebelumnya tetap tersedia.');
        }
        $ink = imagecolorallocate($image, 28, 43, 61);
        $grid = imagecolorallocate($image, 221, 227, 235);
        $blue = imagecolorallocate($image, 20, 99, 181);
        $orange = imagecolorallocate($image, 187, 78, 0);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        try {
            $prediction = $summary['data_kind'] === 'prediction';
            $this->text($image, 32, 85, 50, $prediction ? 'Grafik prediksi pasang surut' : 'Grafik tinggi muka air - data pasang surut', $ink);
            $this->text($image, 19, 85, 86, $prediction ? 'Prediksi dari file sumber; bukan pengukuran langsung.' : 'Jenis data belum diverifikasi; tidak otomatis dianggap sebagai hasil pengukuran.', $ink);
            $this->text($image, 19, 85, 120, 'Periode: '.$summary['start'].' sampai '.$summary['end'].' | '.$timezoneLabel, $ink);
            $this->text($image, 19, 85, 153, 'Acuan tinggi (datum): '.$this->short($summary['datum'] ?: 'Belum diketahui', 105), $ink);
            $this->text($image, 20, 85, 178, 'Tinggi muka air (m)', $ink);

            $decimals = max(0, min(10, -(int) floor(log10($step))));
            for ($i = 0; $i <= (int) round(($yMax - $yMin) / $step); $i++) {
                $value = $yMin + $i * $step;
                $position = $y($value);
                imageline($image, self::LEFT, $position, self::LEFT + self::PLOT_WIDTH, $position, $grid);
                $this->text($image, 18, 15, $position + 6, number_format(abs($value) < $step * 1e-6 ? 0 : $value, $decimals, '.', ''), $ink);
            }
            for ($i = 0; $i <= 5; $i++) {
                $instant = (int) round($start + ($end - $start) * $i / 5);
                $position = $x($instant);
                imageline($image, $position, self::TOP, $position, self::TOP + self::PLOT_HEIGHT, $grid);
                $date = (new DateTimeImmutable('@'.$instant))->setTimezone($zone);
                $this->text($image, 17, $position - 56, 636, $date->format('Y-m-d'), $ink);
                $this->text($image, 17, $position - 42, 663, $date->format('H:i:s'), $ink);
            }
            imagerectangle($image, self::LEFT, self::TOP, self::LEFT + self::PLOT_WIDTH, self::TOP + self::PLOT_HEIGHT, $ink);
            $this->text($image, 20, 580, 701, 'Tanggal dan waktu - '.$timezoneLabel, $ink);
            $previous = null;
            $gapCount = 0;
            foreach ($points as $time => $point) {
                if ($previous !== null) {
                    if ($time - $previous['time'] <= $gapThreshold) {
                        imageline($image, $x($previous['time']), $y($previous['height']), $x($time), $y($point['height']), $blue);
                    } else {
                        $gapCount++;
                    }
                }
                // Retain isolated samples as pixels; larger symbols are readable for sparse tables.
                imagefilledellipse($image, $x($time), $y($point['height']), count($points) <= 200 ? 5 : 1, count($points) <= 200 ? 5 : 1, $blue);
                $previous = ['time' => $time, 'height' => $point['height']];
            }
            foreach (['minimum', 'maximum'] as $extreme) {
                $instant = (new DateTimeImmutable($summary[$extreme.'_at'], $zone))->getTimestamp();
                imageellipse($image, $x($instant), $y($summary[$extreme.'_m']), 13, 13, $orange);
            }
            $this->text($image, 18, 85, 750, 'Biru: '.$summary['count'].' sampel asli; garis hanya penghubung visual, tanpa pemulusan / pengisian data.', $ink);
            $this->text($image, 18, 85, 780, 'Jeda > '.number_format($gapThreshold, 1, '.', '').' detik (1,5 x median interval) diputus: '.$gapCount.' jeda.', $ink);
            $this->text($image, 18, 85, 810, 'Lingkaran: min '.number_format($summary['minimum_m'], 3, '.', '').' m; maks '.number_format($summary['maximum_m'], 3, '.', '').' m, selama periode file; bukan HAT / LAT.', $ink);
            $this->text($image, 18, 85, 850, 'Sumber: '.$this->short((string) ($source['label'] ?? 'File unggahan pengguna'), 100), $ink);
            $this->text($image, 18, 85, 880, 'Kredit: '.$this->short((string) ($source['attribution'] ?? 'Belum dicantumkan'), 100), $ink);
            $this->text($image, 17, 85, 910, 'Diakses: '.($source['accessed_at'] ?? '-').' | File: '.$this->short((string) ($source['file_name'] ?? '-'), 95), $ink);
            $this->text($image, 16, 85, 943, 'SHA-256 sumber: '.($source['file_checksum'] ?? 'belum dicantumkan'), $ink);
            $this->text($image, 17, 85, 978, 'Datum dan kecocokan lokasi mengikuti sumber; grafik ini tidak menetapkan datum atau prediksi baru.', $ink);
            $station = $source['tide_file_metadata'] ?? [];
            $this->text($image, 17, 85, 1008, 'Stasiun sesuai file: '.$this->short((string) ($station['station_name'] ?? 'tidak dicantumkan'), 35)
                .' | Lintang: '.($station['latitude'] ?? '-').' | Bujur: '.($station['longitude'] ?? '-'), $ink);
            $this->text($image, 16, 85, 1042, 'Diolah backend | '.self::METHOD.' | Tanpa layanan AI', $ink);
            ob_start();
            try {
                if (! imagepng($image)) {
                    throw new InvalidTideData('Grafik belum dapat dibuat. Coba lagi; data sebelumnya tetap tersedia.');
                }
                $png = ob_get_contents();
            } finally {
                ob_end_clean();
            }
        } finally {
            imagedestroy($image);
        }

        return ['png' => $png, 'chart' => ['method' => self::METHOD, 'summary' => $summary, 'height_unit' => 'm',
            'plotted_samples' => count($points), 'gap_threshold_seconds' => $gapThreshold, 'gap_count' => $gapCount,
            'y_min_m' => $yMin, 'y_max_m' => $yMax, 'line_method' => 'visual_connector_no_smoothing_no_gap_filling']];
    }

    private function text(\GdImage $image, int $size, int $x, int $baseline, string $text, int $color): void
    {
        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
        if (function_exists('imagettftext') && is_file($font)) {
            imagettftext($image, $size * 0.75, 0, $x, $baseline, $color, $font, $text);
        } else {
            imagestring($image, $size >= 24 ? 5 : 3, $x, $baseline - 14, Str::ascii($text), $color);
        }
    }

    private function short(string $text, int $length): string
    {
        return mb_strimwidth(preg_replace('/\s+/u', ' ', $text), 0, $length, '...');
    }
}
