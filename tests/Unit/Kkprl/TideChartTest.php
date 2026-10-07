<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\InvalidTideData;
use App\Services\KkprlTideChartRenderer;
use App\Services\KkprlTideDataAnalyzer;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TideChartTest extends TestCase
{
    private function tideOptions(): array
    {
        return ['date_column' => 0, 'height_column' => 1, 'time_column' => null,
            'unit' => 'cm', 'timezone' => 'UTC', 'date_format' => 'ymd', 'datum' => 'Nol palem'];
    }

    private function table(array $rows): array
    {
        return ['columns' => ['waktu', 'tinggi'], 'rows' => $rows, 'calendar' => Date::CALENDAR_WINDOWS_1900];
    }

    #[Test]
    public function chart_and_calculations_share_sorted_utc_instants_and_metre_values(): void
    {
        $table = $this->table([['2026-10-01 02:00', -50], ['2026-10-01 00:00', 100], ['2026-10-01 01:00', 150]]);
        $series = app(KkprlTideDataAnalyzer::class)->series($table, $this->tideOptions());
        $this->assertSame([1790812800, 1790816400, 1790820000], array_keys($series['points']));
        $this->assertSame([1.0, 1.5, -0.5], array_column($series['points'], 'height'));
        $chart = app(KkprlTideChartRenderer::class)->build($table, $this->tideOptions(), ['label' => 'Sumber uji']);
        $this->assertSame($series['summary'], $chart['chart']['summary']);
        $this->assertSame('m', $chart['chart']['height_unit']);
        $this->assertSame(3, $chart['chart']['plotted_samples']);
        $this->assertSame(0, $chart['chart']['gap_count']);
        $this->assertSame('image/png', getimagesizefromstring($chart['png'])['mime']);
    }

    #[Test]
    public function big_utc_and_local_columns_produce_identical_physical_samples(): void
    {
        $table = $this->table([['2026-10-01 00:00:00 UTC', '2026-10-01 09:00:00 WIT', '0.5'],
            ['2026-10-01 01:00:00 UTC', '2026-10-01 10:00:00 WIT', '1.2']]);
        $table['columns'] = ['timestamp_utc', 'timestamp_local', 'predicted_height_m'];
        $options = array_replace($this->tideOptions(), ['height_column' => 2, 'unit' => 'm']);
        $utc = app(KkprlTideDataAnalyzer::class)->series($table, $options);
        $local = app(KkprlTideDataAnalyzer::class)->series($table, array_replace($options, ['date_column' => 1, 'timezone' => 'Asia/Jayapura']));
        $this->assertSame(array_keys($utc['points']), array_keys($local['points']));
        $this->assertSame(array_column($utc['points'], 'height'), array_column($local['points'], 'height'));
        $chart = app(KkprlTideChartRenderer::class)->build($table, $options, []);
        $this->assertSame('prediction', $chart['chart']['summary']['data_kind']);
    }

    #[Test]
    public function long_gap_is_blank_rather_than_a_continuous_tidal_curve(): void
    {
        $table = $this->table([['2026-10-01 00:00', 0], ['2026-10-01 01:00', 100],
            ['2026-10-01 02:00', 0], ['2026-10-01 12:00', 200]]);
        $result = app(KkprlTideChartRenderer::class)->build($table, $this->tideOptions(), []);
        $this->assertSame(1, $result['chart']['gap_count']);
        $this->assertSame(5400.0, $result['chart']['gap_threshold_seconds']);
        $image = imagecreatefromstring($result['png']);
        // Halfway along the time axis lies in the ten-hour gap, not halfway along row numbers.
        $x = KkprlTideChartRenderer::LEFT + intdiv(KkprlTideChartRenderer::PLOT_WIDTH, 2);
        $blue = 0x1463B5;
        $bluePixels = 0;
        for ($y = KkprlTideChartRenderer::TOP; $y <= KkprlTideChartRenderer::TOP + KkprlTideChartRenderer::PLOT_HEIGHT; $y++) {
            $bluePixels += (imagecolorat($image, $x, $y) & 0xFFFFFF) === $blue ? 1 : 0;
        }
        $this->assertSame(0, $bluePixels, 'A blank data gap must not contain a connecting blue curve.');
        imagedestroy($image);
    }

    #[Test]
    public function a_constant_negative_series_has_a_finite_nonzero_vertical_scale(): void
    {
        $result = app(KkprlTideChartRenderer::class)->build($this->table([['2026-10-01 00:00', -100], ['2026-10-01 01:00', -100]]), $this->tideOptions(), []);
        $this->assertLessThan(-1, $result['chart']['y_min_m']);
        $this->assertGreaterThan(-1, $result['chart']['y_max_m']);
        $this->assertSame(-1.0, $result['chart']['summary']['mean_m']);
        $this->assertSame('image/png', getimagesizefromstring($result['png'])['mime']);
    }

    #[Test]
    public function all_monthly_minute_samples_and_extremes_are_retained(): void
    {
        $rows = [];
        for ($i = 0; $i <= 31 * 24 * 60; $i++) {
            $rows[] = [gmdate('Y-m-d H:i:s', 1790812800 + $i * 60), $i === 23456 ? -999 : 100 * sin($i / 120)];
        }
        $result = app(KkprlTideChartRenderer::class)->build($this->table($rows), $this->tideOptions(), []);
        $this->assertSame(44641, $result['chart']['plotted_samples']);
        $this->assertSame(-9.99, $result['chart']['summary']['minimum_m']);
        $this->assertSame(0, $result['chart']['gap_count']);
        $this->assertSame('2026-11-01 00:00:00', $result['chart']['summary']['end']);
    }

    #[Test]
    public function invalid_or_duplicate_samples_are_not_silently_omitted_from_a_chart(): void
    {
        $this->expectException(InvalidTideData::class);
        app(KkprlTideChartRenderer::class)->build($this->table([['2026-10-01 00:00', 100], ['2026-10-01 00:00', 200]]), $this->tideOptions(), []);
    }
}
