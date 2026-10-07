<?php

namespace App\Services;

use App\Domain\Kkprl\InvalidTideData;
use DateTimeImmutable;
use DateTimeZone;
use PhpOffice\PhpSpreadsheet\Shared\Date;

final class KkprlTideDataAnalyzer
{
    public function analyze(array $table, array $options, bool $withHarmonics = false): array
    {
        return $this->series($table, $options, $withHarmonics)['summary'];
    }

    /** Canonical UTC instants and metre heights shared by calculations and the chart. */
    public function series(array $table, array $options, bool $withHarmonics = false): array
    {
        $dateColumn = $options['date_column'] ?? null;
        $heightColumn = $options['height_column'] ?? null;
        $timeColumn = $options['time_column'] ?? null;
        $unit = $options['unit'] ?? '';
        $timezone = $options['timezone'] ?? '';
        $format = $options['date_format'] ?? '';
        if (! is_int($dateColumn) || ! is_int($heightColumn) || $dateColumn === $heightColumn
            || ! isset($table['columns'][$dateColumn], $table['columns'][$heightColumn])
            || ($timeColumn !== null && (! is_int($timeColumn) || ! isset($table['columns'][$timeColumn]) || in_array($timeColumn, [$dateColumn, $heightColumn], true)))
            || ! in_array($unit, ['m', 'cm', 'mm'], true)
            || ! in_array($timezone, ['UTC', 'Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'], true)
            || ! in_array($format, ['ymd', 'dmy'], true)) {
            throw new InvalidTideData('Periksa pilihan kolom waktu, tinggi, satuan, format tanggal dan zona waktu.');
        }
        $factor = ['m' => 1, 'cm' => 0.01, 'mm' => 0.001][$unit];
        if (strtolower(trim($table['columns'][$dateColumn])) === 'timestamp_utc' && $timezone !== 'UTC') {
            throw new InvalidTideData('Kolom timestamp_utc memakai zona waktu UTC. Pilih UTC atau gunakan kolom timestamp_local dengan zona waktu yang sesuai.');
        }
        if (strtolower(trim($table['columns'][$heightColumn])) === 'predicted_height_m' && $unit !== 'm') {
            throw new InvalidTideData('Kolom predicted_height_m berisi tinggi dalam meter. Pilih satuan Meter (m).');
        }
        $points = [];
        $zone = new DateTimeZone($timezone);
        foreach ($table['rows'] as $index => $row) {
            if (count(array_filter($row, fn ($value) => $value !== null && trim((string) $value) !== '')) === 0) {
                continue;
            }
            $line = $index + ($table['header_line'] ?? 1) + 1;
            $height = str_replace(',', '.', trim((string) ($row[$heightColumn] ?? '')));
            if (! preg_match('/^[+-]?\d+(?:\.\d+)?$/', $height) || ! is_finite((float) $height) || abs((float) $height) > 1e9) {
                throw new InvalidTideData("Tinggi pada baris {$line} harus berupa angka tanpa pemisah ribuan. Perbaiki baris tersebut; data tidak dilewati otomatis.");
            }
            $time = $this->timestamp($row[$dateColumn] ?? null, $timeColumn === null ? null : ($row[$timeColumn] ?? ''), $format, $zone, $table['calendar']);
            if ($time === null) {
                throw new InvalidTideData("Tanggal/waktu pada baris {$line} tidak sesuai format yang dipilih. Periksa format, kolom jam terpisah dan zona waktu; penanda UTC/WIB/WITA/WIT harus sesuai zona waktu pilihan.");
            }
            $key = $time->getTimestamp();
            if (isset($points[$key])) {
                throw new InvalidTideData("Waktu duplikat pada baris {$line}. Setiap waktu hanya boleh memiliki satu nilai tinggi.");
            }
            $points[$key] = ['time' => $time->format('Y-m-d H:i:s'), 'height' => (float) $height * $factor];
        }
        if (count($points) < 2) {
            throw new InvalidTideData('Data pasut memerlukan sedikitnya dua sampel pada waktu berbeda.');
        }
        ksort($points, SORT_NUMERIC);
        $values = array_column($points, 'height');
        $times = array_keys($points);
        $minimum = min($values);
        $maximum = max($values);
        $intervals = [];
        for ($i = 1; $i < count($times); $i++) {
            $intervals[] = $times[$i] - $times[$i - 1];
        }
        $ordered = array_values($points);

        $summary = [
            'data_kind' => strtolower(trim($table['columns'][$heightColumn])) === 'predicted_height_m' ? 'prediction' : 'unspecified',
            'count' => count($points), 'start' => $ordered[0]['time'], 'end' => $ordered[array_key_last($ordered)]['time'],
            'timezone' => $timezone, 'source_unit' => $unit, 'datum' => trim((string) ($options['datum'] ?? 'Belum diketahui')),
            'minimum_m' => $minimum, 'minimum_at' => $ordered[array_search($minimum, $values, true)]['time'],
            'maximum_m' => $maximum, 'maximum_at' => $ordered[array_search($maximum, $values, true)]['time'],
            'mean_m' => array_sum($values) / count($values), 'range_m' => $maximum - $minimum,
            'duration_hours' => ($times[array_key_last($times)] - $times[0]) / 3600,
            'interval_min_seconds' => min($intervals), 'interval_max_seconds' => max($intervals),
        ];

        if ($withHarmonics) {
            $summary['harmonic'] = (new KkprlTideHarmonicAnalyzer)->analyze($points);
        }

        return ['points' => $points, 'summary' => $summary];
    }

    public function report(array $summary): string
    {
        $n = fn ($value): string => number_format((float) $value, 3, ',', '.');

        $kind = ($summary['data_kind'] ?? '') === 'prediction' ? 'Data merupakan prediksi pasang surut, bukan pengukuran langsung. ' : '';

        $report = $kind.'Ringkasan perhitungan data pasut: '.$summary['count'].' sampel; periode '.$summary['start'].' sampai '.$summary['end'].' ('.$summary['timezone'].'). '
            .'Acuan tinggi: '.$summary['datum'].'. Tinggi minimum '.$n($summary['minimum_m']).' m pada '.$summary['minimum_at'].'; '
            .'maksimum '.$n($summary['maximum_m']).' m pada '.$summary['maximum_at'].'; '
            .'rata-rata aritmetika sampel '.$n($summary['mean_m']).' m; rentang maksimum–minimum '.$n($summary['range_m']).' m. '
            .'Interval antar-sampel '.$summary['interval_min_seconds'].'–'.$summary['interval_max_seconds'].' detik. '
            .'Angka berlaku untuk periode dan acuan data yang diberikan. Rata-rata sampel bukan MSL; minimum/maksimum periode bukan HHWL/LLWL/HAT/LAT. '
            .'Tidak dilakukan interpolasi. Interval tidak seragam atau periode pendek membatasi keterwakilan hasil.';
        if (! isset($summary['harmonic'])) {
            return $report.' Analisis harmonik dan estimasi Formzahl belum dilakukan.';
        }
        $harmonic = $summary['harmonic'];
        $report .= "\n\nAnalisis harmonik: kuadrat terkecil dengan QR, delapan komponen (M2, S2, K1, O1, N2, Q1, M4, MS4), konstanta dan tren linear. ";
        if ($harmonic['status'] === 'estimated') {
            $report .= 'Estimasi Formzahl F = (K1 + O1) / (M2 + S2) = '.$n($harmonic['formzahl'])
                .'; estimasi tipe pasut: '.KkprlTideHarmonicAnalyzer::TYPE_LABELS[$harmonic['tidal_type']].'. ';
            $report .= 'Amplitudo efektif: '.implode('; ', array_map(fn ($name) => $name.' '.$n($harmonic['constituents'][$name]['amplitude_m']).' m', ['M2', 'S2', 'K1', 'O1'])).'. ';
        } else {
            $report .= 'Formzahl dan tipe pasut belum dapat ditetapkan. '.implode(' ', $harmonic['reasons']).' ';
        }
        if ($harmonic['rmse_m'] !== null) {
            $report .= 'RMSE '.$n($harmonic['rmse_m']).' m; R² model '.$n($harmonic['r_squared']).'; R² pasut setelah pemisahan tren '.$n($harmonic['tidal_r_squared']).'. ';
        }

        return $report.implode(' ', $harmonic['warnings']);
    }

    private function timestamp(mixed $date, mixed $clock, string $format, DateTimeZone $zone, int $calendar): ?DateTimeImmutable
    {
        if (is_int($date) || is_float($date)) {
            if ($date < 1 || $date > 2958465 || ! is_finite((float) $date)) {
                return null;
            }
            $old = Date::getExcelCalendar();
            try {
                Date::setExcelCalendar($calendar);
                $value = DateTimeImmutable::createFromMutable(Date::excelToDateTimeObject($date, $zone));
            } finally {
                Date::setExcelCalendar($old);
            }
            if ($clock === null) {
                return $value;
            }
            $date = $value->format($format === 'ymd' ? 'Y-m-d' : 'd/m/Y');
        }
        $date = trim((string) $date);
        if ($clock !== null) {
            if (is_int($clock) || is_float($clock)) {
                if ($clock < 0 || $clock >= 1) {
                    return null;
                }
                $seconds = (int) round($clock * 86400);
                if ($seconds >= 86400) {
                    return null;
                }
                $clock = sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
            }
            $date .= ' '.trim((string) $clock);
        }
        $date = preg_replace('/(?<=\d)T(?=\d)/', ' ', $date);
        if (preg_match('/\s+(UTC|WIB|WITA|WIT)$/', $date, $matches)) {
            $zones = ['UTC' => 'UTC', 'WIB' => 'Asia/Jakarta', 'WITA' => 'Asia/Makassar', 'WIT' => 'Asia/Jayapura'];
            if ($zones[$matches[1]] !== $zone->getName()) {
                return null;
            }
            $date = trim(substr($date, 0, -strlen($matches[1])));
        }
        $formats = $format === 'ymd' ? ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] : ['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'd-m-Y H:i:s', 'd-m-Y H:i', 'd-m-Y'];
        foreach ($formats as $candidate) {
            $time = DateTimeImmutable::createFromFormat('!'.$candidate, $date, $zone);
            $errors = DateTimeImmutable::getLastErrors();
            if ($time !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $time;
            }
        }

        return null;
    }
}
