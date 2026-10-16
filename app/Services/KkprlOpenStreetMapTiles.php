<?php

namespace App\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class KkprlOpenStreetMapTiles
{
    public function tile(int $zoom, int $column, int $row, float $deadline): string
    {
        if ($zoom < 0 || $zoom > 19 || $row < 0 || $row >= 2 ** $zoom) {
            $this->unavailable();
        }
        $size = 2 ** $zoom;
        $column = (($column % $size) + $size) % $size;
        $key = 'kkprl-osm:base64-v1:'.$zoom.':'.$column.':'.$row;
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
        // Identification contains the configured public origin, never applicant metadata.
        $url = parse_url((string) config('app.url'));
        $origin = isset($url['scheme'], $url['host']) && in_array($url['scheme'], ['http', 'https'], true)
            ? $url['scheme'].'://'.$url['host'].(isset($url['port']) ? ':'.$url['port'] : '').'/'
            : 'http://localhost/';
        try {
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
                ->withUserAgent('KKPRL-LocationMap/1.0 (+'.$origin.')')
                ->withHeaders(['Referer' => $origin])
                ->get("https://tile.openstreetmap.org/{$zoom}/{$column}/{$row}.png");
            $binary = $response->body();
            if (! $response->successful() || ! $this->valid($binary)) {
                $this->unavailable();
            }
        } catch (ConnectionException|GuzzleException|\RuntimeException) {
            $this->unavailable();
        }
        $ttl = 7 * 86400;
        if (preg_match('/(?:^|,)\s*max-age=(\d{1,10})(?:\s*,|\s*$)/i', $response->header('Cache-Control') ?? '', $match)) {
            $ttl = max($ttl, min(2147483647, (int) $match[1]));
        }
        if ($expires = strtotime($response->header('Expires') ?? '')) {
            $ttl = max($ttl, $expires - now()->timestamp);
        }
        Cache::put($key, base64_encode($binary), $ttl);

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
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return false;
        }
        imagedestroy($image);

        return true;
    }

    private function unavailable(): never
    {
        throw ValidationException::withMessages(['location_map' => 'Peta jalan belum tersedia. Dokumen belum dibuat. Coba generate kembali setelah koneksi tersedia.']);
    }
}
