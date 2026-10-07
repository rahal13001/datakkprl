<?php

namespace App\Services;

final class KkprlTideHarmonicAnalyzer
{
    public const METHOD = 'window_harmonic_ols_qr_v1';

    // Standard constituent angular speeds, degrees/hour. See ADR-019 for references and scope.
    private const SPEEDS = ['M2' => 28.9841042, 'S2' => 30.0, 'K1' => 15.0410686, 'O1' => 13.9430356,
        'N2' => 28.4397295, 'Q1' => 13.3986609, 'M4' => 57.9682084, 'MS4' => 58.9841042];

    public const TYPE_LABELS = [
        'semidiurnal' => 'Harian ganda',
        'mixed_semidiurnal' => 'Campuran condong harian ganda',
        'mixed_diurnal' => 'Campuran condong harian tunggal',
        'diurnal' => 'Harian tunggal',
    ];

    /** @param array<int, array{height: float}> $points Validated samples keyed by UTC epoch seconds. */
    public function analyze(array $points): array
    {
        $result = ['method' => self::METHOD, 'status' => 'unavailable', 'formzahl' => null, 'tidal_type' => null,
            'constituents' => [], 'rmse_m' => null, 'r_squared' => null, 'tidal_r_squared' => null, 'coverage' => null,
            'nodal_corrections' => false, 'amplitude_basis' => 'effective_record_window',
            'reasons' => [], 'warnings' => [
                'Amplitudo dan Formzahl merupakan estimasi efektif untuk periode data, bukan konstanta harmonik astronomis stasiun.',
                'P1–K1 dan K2–S2 belum dipisahkan dalam model ini; komponen yang tidak dimodelkan dapat memengaruhi amplitudo dan tipe pasut.',
                'Tidak diterapkan koreksi nodal, inferensi astronomis atau interval kepercayaan. Kecocokan model bukan bukti kecocokan stasiun dengan lokasi proposal.',
            ]];
        if (count($points) < 200) {
            $result['reasons'][] = 'Estimasi harmonik memerlukan sedikitnya 200 sampel dan rentang minimal 28 hari.';

            return $result;
        }
        ksort($points, SORT_NUMERIC);
        $times = array_keys($points);
        $start = $times[0];
        $duration = $times[array_key_last($times)] - $start;
        $result['reference_time_utc'] = gmdate('Y-m-d H:i:s', $start);
        if ($duration < 28 * 86400) {
            $result['reasons'][] = 'Rentang data kurang dari 28 hari; komponen utama dan komponen pendukung belum cukup terpisah.';

            return $result;
        }
        $intervals = [];
        for ($i = 1; $i < count($times); $i++) {
            $intervals[] = $times[$i] - $times[$i - 1];
        }
        sort($intervals, SORT_NUMERIC);
        $middle = intdiv(count($intervals), 2);
        $median = count($intervals) % 2 ? $intervals[$middle] : ($intervals[$middle - 1] + $intervals[$middle]) / 2;
        $result['coverage'] = min(1.0, count($points) / ($duration / $median + 1));
        if ($result['coverage'] < 0.9 || max($intervals) > 10800) {
            $result['reasons'][] = 'Cakupan sampel kurang dari 90% atau terdapat jeda lebih dari 3 jam. Formzahl tidak ditetapkan; data tidak diinterpolasi.';

            return $result;
        }
        $values = array_column($points, 'height');
        $mean = array_sum($values) / count($values);
        $variance = 0.0;
        foreach ($values as $height) {
            $variance += ($height - $mean) ** 2;
        }
        if ($variance / count($values) < 1e-16) {
            $result['reasons'][] = 'Variasi tinggi terlalu kecil untuk menghitung Formzahl.';

            return $result;
        }
        // Incremental QR with Givens rotations avoids storing an N×P matrix or squaring its condition number.
        $width = 2 + 2 * count(self::SPEEDS);
        $upper = array_fill(0, $width, array_fill(0, $width, 0.0));
        $projected = array_fill(0, $width, 0.0);
        $residualSquares = 0.0;
        foreach ($points as $time => $point) {
            $hours = ($time - $start) / 3600;
            $row = [1.0, 2 * ($time - $start) / $duration - 1];
            foreach (self::SPEEDS as $speed) {
                $angle = deg2rad($speed * $hours);
                $row[] = cos($angle);
                $row[] = sin($angle);
            }
            $height = $point['height'] - $mean;
            for ($j = 0; $j < $width; $j++) {
                $radius = hypot($upper[$j][$j], $row[$j]);
                if ($radius === 0.0) {
                    continue;
                }
                $cosine = $upper[$j][$j] / $radius;
                $sine = $row[$j] / $radius;
                for ($k = $j; $k < $width; $k++) {
                    $previous = $upper[$j][$k];
                    $upper[$j][$k] = $cosine * $previous + $sine * $row[$k];
                    $row[$k] = -$sine * $previous + $cosine * $row[$k];
                }
                $previous = $projected[$j];
                $projected[$j] = $cosine * $previous + $sine * $height;
                $height = -$sine * $previous + $cosine * $height;
            }
            $residualSquares += $height ** 2;
        }
        $diagonal = array_map(fn ($i) => abs($upper[$i][$i]), range(0, $width - 1));
        if (min($diagonal) < max($diagonal) * 1e-8) {
            $result['status'] = 'unreliable';
            $result['reasons'][] = 'Model numerik tidak cukup stabil untuk memisahkan komponen pasut.';

            return $result;
        }
        $coefficients = array_fill(0, $width, 0.0);
        for ($j = $width - 1; $j >= 0; $j--) {
            $value = $projected[$j];
            for ($k = $j + 1; $k < $width; $k++) {
                $value -= $upper[$j][$k] * $coefficients[$k];
            }
            $coefficients[$j] = $value / $upper[$j][$j];
        }
        $result['rmse_m'] = sqrt($residualSquares / count($points));
        $result['r_squared'] = max(0.0, min(1.0, 1 - $residualSquares / $variance));
        $tidalSquares = 0.0;
        foreach ($points as $time => $point) {
            $baseline = $mean + $coefficients[0] + $coefficients[1] * (2 * ($time - $start) / $duration - 1);
            $tidalSquares += ($point['height'] - $baseline) ** 2;
        }
        // A large drift can make total R² look excellent while the tidal part fits poorly.
        $result['tidal_r_squared'] = $tidalSquares > 1e-16 ? max(0.0, min(1.0, 1 - $residualSquares / $tidalSquares)) : 0.0;
        $result['linear_trend_m_per_day'] = $coefficients[1] * 2 / ($duration / 86400);
        foreach (array_keys(self::SPEEDS) as $i => $name) {
            $result['constituents'][$name] = ['amplitude_m' => hypot($coefficients[2 + 2 * $i], $coefficients[3 + 2 * $i]),
                'speed_degrees_per_hour' => self::SPEEDS[$name]];
        }
        $amplitudes = array_map(fn ($constituent) => $constituent['amplitude_m'], $result['constituents']);
        $denominator = $amplitudes['M2'] + $amplitudes['S2'];
        if ($result['tidal_r_squared'] < 0.9 || $denominator < max(1e-8, sqrt($tidalSquares / count($values)) * 1e-6)) {
            $result['status'] = 'unreliable';
            $result['reasons'][] = $result['tidal_r_squared'] < 0.9
                ? 'Kecocokan model pasut setelah pemisahan tren (R² pasut) kurang dari 0,90. Formzahl dan tipe pasut tidak ditetapkan.'
                : 'Jumlah amplitudo M2 dan S2 terlalu kecil; pembagian Formzahl tidak stabil.';

            return $result;
        }
        $result['formzahl'] = ($amplitudes['K1'] + $amplitudes['O1']) / $denominator;
        if (! is_finite($result['formzahl'])) {
            $result['formzahl'] = null;
            $result['status'] = 'unreliable';
            $result['reasons'][] = 'Nilai Formzahl tidak hingga; tipe pasut tidak ditetapkan.';

            return $result;
        }
        $result['status'] = 'estimated';
        $result['tidal_type'] = $this->classify($result['formzahl']);
        foreach ([0.25, 1.5, 3.0] as $boundary) {
            if (abs($result['formzahl'] - $boundary) <= 0.05 * $boundary) {
                $result['warnings'][] = 'Nilai F berdekatan dengan batas klasifikasi; tipe pasut perlu ditinjau menggunakan data atau konstanta stasiun yang lebih lengkap.';
                break;
            }
        }

        return $result;
    }

    public function classify(float $formzahl): string
    {
        return match (true) {
            $formzahl < 0.25 => 'semidiurnal',
            $formzahl < 1.5 => 'mixed_semidiurnal',
            $formzahl < 3.0 => 'mixed_diurnal',
            default => 'diurnal',
        };
    }
}
