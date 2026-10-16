<?php

namespace App\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class KkprlWorldCoverTiles
{
    public const LAYER = 'esa-worldcover-s2rgbnir-10m-2021-v2_tcc';

    public function tile(int $zoom, int $column, int $row, float $deadline): string
    {
        $size = 2 ** $zoom;
        if ($zoom < 0 || $zoom > 14 || $row < 0 || $row >= $size) {
            $this->unavailable();
        }
        $column = (($column % $size) + $size) % $size;
        $key = 'kkprl-worldcover-2021:base64-v1:'.$zoom.':'.$column.':'.$row;
        $cached = Cache::get($key);
        if (is_string($cached) && strlen($cached) <= 699052) {
            $decoded = base64_decode($cached, true);
            if (is_string($decoded) && $this->valid($decoded)) {
                return $decoded;
            }
        }
        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            $this->unavailable();
        }
        try {
            // Fixed public host and numeric tile indices only. No user URL or redirect.
            $response = Http::withOptions([
                'allow_redirects' => false,
                'on_headers' => function ($response): void {
                    if ((int) $response->getHeaderLine('Content-Length') > 524288) {
                        throw new \RuntimeException('Tile exceeds size limit.');
                    }
                },
                'progress' => function ($total, $downloaded): void {
                    if ($downloaded > 524288) {
                        throw new \RuntimeException('Tile exceeds size limit.');
                    }
                },
            ])->connectTimeout(min(4, $remaining))->timeout(min(6, $remaining))
                ->withUserAgent('KKPRL-LocationMap/1.0')
                ->get('https://wmts.terrascope.be/', [
                    'SERVICE' => 'WMTS', 'REQUEST' => 'GetTile', 'VERSION' => '1.0.0',
                    'LAYER' => self::LAYER, 'STYLE' => 'default', 'FORMAT' => 'image/png',
                    'TILEMATRIXSET' => 'EPSG:3857', 'TILEMATRIX' => $zoom,
                    'TILECOL' => $column, 'TILEROW' => $row, 'TIME' => '2021-01-01',
                ]);
            $binary = $response->body();
            if (! $response->successful() || ! $this->valid($binary)) {
                $this->unavailable();
            }
        } catch (ConnectionException|GuzzleException|\RuntimeException $exception) {
            $this->unavailable();
        }
        // MySQL database cache uses a UTF-8 text column, which rejects raw PNG bytes.
        Cache::put($key, base64_encode($binary), now()->addDays(7));

        return $binary;
    }

    private function valid(string $binary): bool
    {
        if (strlen($binary) > 524288 || ! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return false;
        }
        $size = @getimagesizefromstring($binary);

        if ($size === false || $size[0] !== 256 || $size[1] !== 256 || $size['mime'] !== 'image/png') {
            return false;
        }
        // A valid IHDR alone does not prove the compressed pixel stream is readable.
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return false;
        }
        imagedestroy($image);

        return true;
    }

    private function unavailable(): never
    {
        throw ValidationException::withMessages(['location_map' => 'Citra satelit belum tersedia. Dokumen belum dibuat. Coba generate kembali setelah koneksi tersedia.']);
    }
}
