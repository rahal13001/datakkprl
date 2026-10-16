<?php

namespace App\Domain\Kkprl;

use Illuminate\Validation\ValidationException;

/** WGS84 spherical measurements and normalized Web Mercator coordinates. */
final class ProposalLocationGeometry
{
    private const RADIUS = 6371008.8;

    public readonly array $points;

    private function __construct(public readonly string $shape, public readonly array $latLng)
    {
        $points = [];
        $previous = null;
        foreach ($latLng as [$lat, $lng]) {
            $x = ($lng + 180) / 360;
            if ($previous !== null) {
                while ($x - $previous > .5) {
                    $x -= 1;
                }
                while ($x - $previous < -.5) {
                    $x += 1;
                }
            }
            $points[] = [$x, (1 - asinh(tan(deg2rad($lat))) / M_PI) / 2];
            $previous = $x;
        }
        $this->points = $points;
        if ($shape === 'polygon' && ($this->selfIntersects() || $this->measurement()['value'] <= PHP_FLOAT_EPSILON)) {
            self::invalid('Area peta bersilangan atau tidak memiliki luas. Periksa koordinat Bab 1.');
        }
    }

    public static function fromValues(array $values): self
    {
        $shape = $values['shape_type'] ?? 'point';
        $raw = $values['coordinates_raw'] ?? null;
        if (is_string($raw) && strlen($raw) > 1048576) {
            self::invalid('Data koordinat terlalu besar. Peta mendukung maksimal 1.000 titik.');
        }
        if ($raw !== null) {
            $points = is_string($raw) ? json_decode($raw, true) : $raw;
        } else {
            $points = [];
            foreach (preg_split('/\R/', trim((string) ($values['coordinates'] ?? ''))) as $line) {
                if (! preg_match('/^Titik \d+: Latitude [^()]+\(([^()]+)\), Longitude [^()]+\(([^()]+)\)$/u', trim($line), $matches)) {
                    self::invalid();
                }
                $points[] = ['lat_dd' => trim($matches[1]), 'lng_dd' => trim($matches[2])];
            }
        }
        if (! in_array($shape, ['point', 'line', 'polygon'], true) || ! is_array($points) || ! array_is_list($points) || count($points) > 1000) {
            self::invalid();
        }
        $latLng = [];
        foreach ($points as $point) {
            if (! is_array($point)) {
                self::invalid();
            }
            $lat = $point['lat_dd'] ?? null;
            $lng = $point['lng_dd'] ?? null;
            if (! is_numeric($lat) || ! is_numeric($lng) || ! is_finite((float) $lat) || ! is_finite((float) $lng)
                || abs((float) $lat) > 85.05112878 || abs((float) $lng) > 180) {
                self::invalid('Koordinat peta tidak valid atau di luar cakupan Web Mercator (±85,05°).');
            }
            $latLng[] = [(float) $lat, (float) $lng];
        }
        if ($shape === 'polygon' && count($latLng) > 1 && $latLng[0] === $latLng[array_key_last($latLng)]) {
            array_pop($latLng);
        }
        if (count($latLng) < match ($shape) {
            'polygon' => 3, 'line' => 2, default => 1
        }) {
            self::invalid();
        }

        return new self($shape, $latLng);
    }

    public function measurement(): array
    {
        if ($this->shape === 'point') {
            return ['value' => null, 'label' => 'Lokasi titik', 'unit' => ''];
        }
        $sum = 0.0;
        $count = count($this->latLng);
        for ($i = 0; $i < ($this->shape === 'polygon' ? $count : $count - 1); $i++) {
            [$lat, $lng] = $this->latLng[$i];
            [$nextLat, $nextLng] = $this->latLng[($i + 1) % $count];
            $difference = $nextLng - $lng;
            $difference = $difference > 180 ? $difference - 360 : ($difference < -180 ? $difference + 360 : $difference);
            if ($this->shape === 'polygon') {
                $sum += deg2rad($difference) * (sin(deg2rad($lat)) + sin(deg2rad($nextLat)));
            } else {
                $h = sin(deg2rad($nextLat - $lat) / 2) ** 2 + cos(deg2rad($lat)) * cos(deg2rad($nextLat)) * sin(deg2rad($difference) / 2) ** 2;
                $sum += 2 * self::RADIUS * asin(sqrt(min(1, max(0, $h))));
            }
        }
        $value = $this->shape === 'polygon' ? abs($sum) * self::RADIUS ** 2 / 20000 : $sum / 1000;
        $unit = $this->shape === 'polygon' ? 'ha' : 'km';

        return ['value' => $value, 'label' => number_format($value, 2, ',', '.').' '.$unit, 'unit' => $unit];
    }

    private function selfIntersects(): bool
    {
        $n = count($this->points);
        $cross = static fn ($a, $b, $c) => ($b[0] - $a[0]) * ($c[1] - $a[1]) - ($b[1] - $a[1]) * ($c[0] - $a[0]);
        $on = static fn ($a, $b, $c) => abs($cross($a, $b, $c)) < 1e-18 && $c[0] >= min($a[0], $b[0]) && $c[0] <= max($a[0], $b[0]) && $c[1] >= min($a[1], $b[1]) && $c[1] <= max($a[1], $b[1]);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 2; $j < $n; $j++) {
                if ($i === 0 && $j === $n - 1) {
                    continue;
                }
                [$a, $b, $c, $d] = [$this->points[$i], $this->points[($i + 1) % $n], $this->points[$j], $this->points[($j + 1) % $n]];
                if (($cross($a, $b, $c) * $cross($a, $b, $d) < 0 && $cross($c, $d, $a) * $cross($c, $d, $b) < 0) || $on($a, $b, $c) || $on($a, $b, $d) || $on($c, $d, $a) || $on($c, $d, $b)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function invalid(string $message = 'Koordinat Bab 1 belum dapat dibuat menjadi peta. Periksa titik dan bentuk lokasi.'): never
    {
        throw ValidationException::withMessages(['location_map' => $message]);
    }
}
