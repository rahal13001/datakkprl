<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\ProposalLocationGeometry;
use App\Domain\Kkprl\ProposalLocationMapViewport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProposalLocationMapViewportTest extends TestCase
{
    private function geometry(array $points, string $shape = 'line'): ProposalLocationGeometry
    {
        return ProposalLocationGeometry::fromValues([
            'shape_type' => $shape,
            'coordinates_raw' => json_encode(array_map(fn ($point) => ['lng_dd' => $point[0], 'lat_dd' => $point[1]], $points)),
        ]);
    }

    #[Test]
    public function detail_chooses_print_scale_and_keeps_original_coordinates_and_measurement(): void
    {
        $small = $this->geometry([[131.256, -.88], [131.2564, -.88]]);
        $before = [$small->points, $small->measurement()];
        $view = ProposalLocationMapViewport::detail($small->points);
        $this->assertSame(500, $view->scaleDenominator);
        $this->assertSame('1:500', $view->scaleLabel());
        $printedWidthMetres = 700 / 72 * .0254 * 1190 / 1800;
        $groundWidthMetres = $view->spanX * 2 * M_PI * 6378137 * cos(deg2rad($view->centreLatitude));
        $this->assertEqualsWithDelta(500, $groundWidthMetres / $printedWidthMetres, .001);
        foreach ($small->points as $point) {
            [$x, $y] = $view->project($point);
            $this->assertGreaterThan(90, $x);
            $this->assertLessThan(1280, $x);
            $this->assertGreaterThan(125, $y);
            $this->assertLessThan(985, $y);
        }
        $this->assertSame($before, [$small->points, $small->measurement()]);
        $long = $this->geometry([[131.256, -.88], [131.257, -.88]]);
        $this->assertSame(1000, ProposalLocationMapViewport::detail($long->points)->scaleDenominator);
    }

    #[Test]
    public function tall_polygon_increases_denominator_to_fit_all_points_without_buffering_geometry(): void
    {
        $geometry = $this->geometry([[1, 0], [1.00005, 0], [1.00005, .01], [1, .01]], 'polygon');
        $view = ProposalLocationMapViewport::detail($geometry->points);
        $this->assertGreaterThan(1000, $view->scaleDenominator);
        foreach ($geometry->points as $point) {
            [$x, $y] = $view->project($point);
            $this->assertGreaterThanOrEqual(90, $x);
            $this->assertLessThanOrEqual(1280, $x);
            $this->assertGreaterThanOrEqual(125, $y);
            $this->assertLessThanOrEqual(985, $y);
        }
    }

    #[Test]
    public function inset_is_broader_and_caps_satellite_magnification_at_print_scale_50000(): void
    {
        $geometry = $this->geometry([[131.256, -.88]], 'point');
        $detail = ProposalLocationMapViewport::detail($geometry->points);
        $inset = ProposalLocationMapViewport::inset($detail);
        $this->assertSame(500, $detail->scaleDenominator);
        $this->assertSame(50000, $inset->scaleDenominator);
        $this->assertSame('1:50.000', $inset->scaleLabel());
        $this->assertGreaterThanOrEqual($detail->spanX * 4, $inset->spanX);
        $this->assertGreaterThanOrEqual($detail->spanY * 4, $inset->spanY);
        $this->assertLessThanOrEqual(14, $inset->zoom);
        $this->assertGreaterThanOrEqual(6.8, $inset->metresPerPixel());
        [$x, $y] = $inset->project($geometry->points[0]);
        $this->assertEqualsWithDelta(1537.5, $x, 1);
        $this->assertEqualsWithDelta(880, $y, 1);
    }

    #[Test]
    public function scales_account_for_latitude_and_antimeridian_and_do_not_crop_large_sites(): void
    {
        foreach ([0, -6, 80, -80] as $latitude) {
            $geometry = $this->geometry([[179.99, $latitude], [-179.99, $latitude]]);
            $view = ProposalLocationMapViewport::detail($geometry->points);
            $printedMetresPerPixel = 700 / 1800 / 72 * .0254;
            $this->assertEqualsWithDelta($view->scaleDenominator, $view->metresPerPixel() / $printedMetresPerPixel, .001);
            $this->assertLessThan(.01, $view->spanX);
            $inset = ProposalLocationMapViewport::inset($view);
            $this->assertGreaterThanOrEqual($view->spanX * 4, $inset->spanX);
            foreach ($geometry->points as $point) {
                [$x, $y] = $view->project($point);
                $this->assertGreaterThanOrEqual(90, $x);
                $this->assertLessThanOrEqual(1280, $x);
                $this->assertGreaterThanOrEqual(125, $y);
                $this->assertLessThanOrEqual(985, $y);
            }
        }
    }
}
