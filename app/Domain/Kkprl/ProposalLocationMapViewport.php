<?php

namespace App\Domain\Kkprl;

use Illuminate\Validation\ValidationException;

/** Print scale at the centre of a Web Mercator frame; never changes the source geometry. */
final class ProposalLocationMapViewport
{
    public const IMAGE_WIDTH = 1800;

    public const IMAGE_HEIGHT = 1100;

    public const PRINT_WIDTH_POINTS = 700;

    public const DETAIL_FRAME = [90, 125, 1190, 860];

    public const INSET_FRAME = [1330, 785, 415, 190];

    private const CIRCUMFERENCE = 2 * M_PI * 6378137;

    public readonly float $centreLatitude;

    public readonly int $scaleDenominator;

    public readonly int $zoom;

    private function __construct(
        public readonly float $left,
        public readonly float $top,
        public readonly float $spanX,
        public readonly float $spanY,
        public readonly array $frame,
    ) {
        if ($spanX > 1 || $spanY > 1) {
            throw ValidationException::withMessages(['location_map' => 'Cakupan koordinat terlalu luas untuk satu peta lokasi. Periksa koordinat.']);
        }
        $this->centreLatitude = self::latitude($top + $spanY / 2);
        $this->scaleDenominator = (int) round($this->metresPerPixel() / self::printedMetresPerPixel());
        $this->zoom = min(14, max(0, (int) floor(log(3 / max($spanX, $spanY), 2))));
    }

    public static function detail(array $points): self
    {
        $xs = array_column($points, 0);
        $ys = array_column($points, 1);
        $centreX = (min($xs) + max($xs)) / 2;
        $centreY = (min($ys) + max($ys)) / 2;
        $requiredSpanX = max(max($xs) - min($xs), (max($ys) - min($ys)) * self::DETAIL_FRAME[2] / self::DETAIL_FRAME[3]) * 1.35;

        return self::fit($centreX, $centreY, $requiredSpanX, self::DETAIL_FRAME, 500);
    }

    public static function inset(self $detail): self
    {
        $requiredSpanX = max($detail->spanX, $detail->spanY * self::INSET_FRAME[2] / self::INSET_FRAME[3]) * 4;

        return self::fit($detail->left + $detail->spanX / 2, $detail->top + $detail->spanY / 2, $requiredSpanX, self::INSET_FRAME, 50000);
    }

    private static function fit(float $centreX, float $centreY, float $requiredSpanX, array $frame, int $minimum): self
    {
        $circumference = self::CIRCUMFERENCE * cos(deg2rad(self::latitude($centreY)));
        $requiredScale = $requiredSpanX * $circumference / ($frame[2] * self::printedMetresPerPixel());
        $scale = $minimum;
        for ($power = 100; $power <= 1000000000; $power *= 10) {
            foreach ([1, 2, 2.5, 5] as $step) {
                $candidate = (int) ($power * $step);
                if ($candidate >= $minimum && $candidate >= $requiredScale) {
                    $scale = $candidate;
                    break 2;
                }
            }
        }
        $spanX = $frame[2] * self::printedMetresPerPixel() * $scale / $circumference;
        $spanY = $spanX * $frame[3] / $frame[2];

        return new self($centreX - $spanX / 2, max(0, min(1 - $spanY, $centreY - $spanY / 2)), $spanX, $spanY, $frame);
    }

    public function metresPerPixel(): float
    {
        return $this->spanX * self::CIRCUMFERENCE * cos(deg2rad($this->centreLatitude)) / $this->frame[2];
    }

    /** Choose raster resolution within provider/request limits, without changing print scale. */
    public function streetZoom(): int
    {
        $zoom = min(19, max(0, (int) ceil(log($this->frame[2] / ($this->spanX * 256), 2))));
        while ($zoom > 0) {
            $size = 2 ** $zoom;
            $columns = (int) ceil(($this->left + $this->spanX) * $size) - (int) floor($this->left * $size);
            $rows = (int) ceil(($this->top + $this->spanY) * $size) - (int) floor($this->top * $size);
            if ($columns * $rows <= 16) {
                break;
            }
            $zoom--;
        }

        return $zoom;
    }

    public function project(array $point): array
    {
        return [
            $this->frame[0] + ($point[0] - $this->left) / $this->spanX * $this->frame[2],
            $this->frame[1] + ($point[1] - $this->top) / $this->spanY * $this->frame[3],
        ];
    }

    public function scaleLabel(): string
    {
        return '1:'.number_format($this->scaleDenominator, 0, ',', '.');
    }

    private static function printedMetresPerPixel(): float
    {
        return self::PRINT_WIDTH_POINTS / self::IMAGE_WIDTH / 72 * .0254;
    }

    private static function latitude(float $normalizedY): float
    {
        return rad2deg(atan(sinh(M_PI * (1 - 2 * $normalizedY))));
    }
}
