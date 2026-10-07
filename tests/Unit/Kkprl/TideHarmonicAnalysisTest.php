<?php

namespace Tests\Unit\Kkprl;

use App\Services\KkprlTideDataAnalyzer;
use App\Services\KkprlTideHarmonicAnalyzer;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TideHarmonicAnalysisTest extends TestCase
{
    // Independent standard constituent speeds (degrees/hour); fixtures include nuisance constituents and drift.
    private const SPEEDS = ['M2' => 28.9841042, 'S2' => 30.0, 'K1' => 15.0410686, 'O1' => 13.9430356,
        'N2' => 28.4397295, 'Q1' => 13.3986609, 'M4' => 57.9682084, 'MS4' => 58.9841042];

    private function points(array $amplitudes = [], int $days = 31, int $step = 3600, float $offset = 2.7): array
    {
        $amplitudes += ['M2' => 0.6, 'S2' => 0.2, 'K1' => 0.3, 'O1' => 0.1,
            'N2' => 0.09, 'Q1' => 0.04, 'M4' => 0.03, 'MS4' => 0.02];
        $points = [];
        $start = 1788220800;
        for ($t = 0; $t <= $days * 86400; $t += $step) {
            $height = $offset + 0.002 * $t / 86400;
            foreach (self::SPEEDS as $name => $speed) {
                $height += $amplitudes[$name] * cos(deg2rad($speed * $t / 3600) + 0.37 * array_search($name, array_keys(self::SPEEDS), true));
            }
            $points[$start + $t] = ['height' => $height];
        }

        return $points;
    }

    #[Test]
    public function recovers_known_amplitudes_and_formzahl_with_other_constituents_offset_and_drift(): void
    {
        $analysis = (new KkprlTideHarmonicAnalyzer)->analyze($this->points());
        $this->assertSame('estimated', $analysis['status']);
        foreach (['M2' => 0.6, 'S2' => 0.2, 'K1' => 0.3, 'O1' => 0.1, 'N2' => 0.09, 'Q1' => 0.04] as $name => $amplitude) {
            $this->assertEqualsWithDelta($amplitude, $analysis['constituents'][$name]['amplitude_m'], 1e-9);
        }
        $this->assertEqualsWithDelta(0.5, $analysis['formzahl'], 1e-9);
        $this->assertSame('mixed_semidiurnal', $analysis['tidal_type']);
        $this->assertLessThan(1e-10, $analysis['rmse_m']);
        $this->assertEqualsWithDelta(1, $analysis['r_squared'], 1e-10);
        $this->assertFalse($analysis['nodal_corrections']);
        $this->assertStringContainsString('P1', implode(' ', $analysis['warnings']));
    }

    #[Test]
    public function shorter_records_constant_water_levels_and_large_gaps_do_not_produce_a_type(): void
    {
        $cases = [$this->points(days: 15), array_map(fn () => ['height' => 2.0], $this->points())];
        $gaps = $this->points();
        foreach (array_keys($gaps) as $time) {
            if (($time - array_key_first($gaps)) > 10 * 86400 && ($time - array_key_first($gaps)) < 12 * 86400) {
                unset($gaps[$time]);
            }
        }
        $cases[] = $gaps;
        foreach ($cases as $points) {
            $analysis = (new KkprlTideHarmonicAnalyzer)->analyze($points);
            $this->assertNotSame('estimated', $analysis['status']);
            $this->assertNull($analysis['formzahl']);
            $this->assertNull($analysis['tidal_type']);
            $this->assertNotEmpty($analysis['reasons']);
        }
    }

    #[Test]
    public function poor_harmonic_fit_and_zero_semidiurnal_amplitudes_do_not_invent_a_classification(): void
    {
        $poor = $this->points();
        foreach ($poor as $time => &$point) {
            $point['height'] += 4 * sin(($time - array_key_first($poor)) / 3600 * 1.7);
        }
        unset($point);
        foreach ([$poor, $this->points(['M2' => 0, 'S2' => 0])] as $points) {
            $analysis = (new KkprlTideHarmonicAnalyzer)->analyze($points);
            $this->assertSame('unreliable', $analysis['status']);
            $this->assertNull($analysis['formzahl']);
            $this->assertNull($analysis['tidal_type']);
        }
    }

    #[Test]
    public function large_linear_drift_cannot_hide_a_poor_tidal_fit(): void
    {
        $points = $this->points();
        $start = array_key_first($points);
        foreach ($points as $time => &$point) {
            $hours = ($time - $start) / 3600;
            $point['height'] += 10 * $hours + 4 * sin($hours * 1.7);
        }
        unset($point);
        $analysis = (new KkprlTideHarmonicAnalyzer)->analyze($points);
        $this->assertGreaterThan(0.99, $analysis['r_squared']);
        $this->assertLessThan(0.9, $analysis['tidal_r_squared']);
        $this->assertSame('unreliable', $analysis['status']);
        $this->assertNull($analysis['formzahl']);
    }

    #[Test]
    public function normalized_units_timezones_and_missing_samples_do_not_change_the_known_formzahl(): void
    {
        $points = $this->points();
        unset($points[array_key_first($points) + 5 * 3600]);
        foreach (['UTC' => 0, 'Asia/Jayapura' => 9 * 3600] as $zone => $offset) {
            $table = ['columns' => ['datetime', 'height'], 'calendar' => Date::CALENDAR_WINDOWS_1900,
                'rows' => array_map(fn ($time, $point) => [gmdate('Y-m-d H:i:s', $time + $offset), $point['height'] * 100], array_keys($points), array_values($points))];
            $summary = (new KkprlTideDataAnalyzer)->analyze($table, ['date_column' => 0, 'height_column' => 1,
                'unit' => 'cm', 'timezone' => $zone, 'date_format' => 'ymd', 'datum' => 'Nol palem'], withHarmonics: true);
            $this->assertSame(count($points), $summary['count']);
            $this->assertEqualsWithDelta(0.5, $summary['harmonic']['formzahl'], 1e-9);
            $this->assertEqualsWithDelta(0.6, $summary['harmonic']['constituents']['M2']['amplitude_m'], 1e-9);
            $this->assertStringContainsString('estimasi tipe pasut', (new KkprlTideDataAnalyzer)->report($summary));
        }
    }

    public static function formzahlBoundaries(): array
    {
        return [[0.0, 'semidiurnal'], [0.249999, 'semidiurnal'], [0.25, 'mixed_semidiurnal'],
            [1.499999, 'mixed_semidiurnal'], [1.5, 'mixed_diurnal'], [2.999999, 'mixed_diurnal'], [3.0, 'diurnal']];
    }

    #[Test]
    public function sparse_sampling_is_rejected_even_when_no_single_gap_exceeds_three_hours(): void
    {
        $points = $this->points();
        $start = array_key_first($points);
        foreach (array_keys($points) as $time) {
            if (intdiv($time - $start, 3600) % 4 === 1) {
                unset($points[$time]);
            }
        }
        $result = (new KkprlTideHarmonicAnalyzer)->analyze($points);
        $this->assertLessThan(0.9, $result['coverage']);
        $this->assertNull($result['formzahl']);
        $this->assertNull($result['tidal_type']);
    }

    public static function knownRegimes(): array
    {
        return [[0.1, 'semidiurnal'], [0.5, 'mixed_semidiurnal'], [2.0, 'mixed_diurnal'], [4.0, 'diurnal']];
    }

    #[Test]
    #[DataProvider('knownRegimes')]
    public function harmonic_recovery_covers_each_tidal_regime(float $formzahl, string $type): void
    {
        $result = (new KkprlTideHarmonicAnalyzer)->analyze($this->points(['K1' => $formzahl * 0.8 - 0.01, 'O1' => 0.01]));
        $this->assertSame('estimated', $result['status']);
        $this->assertEqualsWithDelta($formzahl, $result['formzahl'], 1e-9);
        $this->assertSame($type, $result['tidal_type']);
    }

    #[Test]
    #[DataProvider('formzahlBoundaries')]
    public function classifies_unrounded_formzahl_with_explicit_boundary_convention(float $formzahl, string $type): void
    {
        $this->assertSame($type, (new KkprlTideHarmonicAnalyzer)->classify($formzahl));
    }
}
