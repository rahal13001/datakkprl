<?php

namespace App\Services;

use App\Domain\Kkprl\InvalidSeabedProfile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class KkprlSeabedProfileService
{
    public const ENDPOINT = 'https://www.gmrt.org/services/ProfileServer';

    public const LIMITATIONS = 'Profil diolah dari grid sekunder GMRT, bukan pengukuran langsung. Jarak antarsampel bukan resolusi grid; resolusi lokal, periode akuisisi dan acuan vertikal tidak diberikan oleh layanan ini. Data dekat pantai atau lintasan pendek dapat terlalu kasar untuk menggambarkan lokasi usaha. Profil tidak membuktikan jenis sedimen, tidak menggantikan survei setempat, dan tidak digunakan untuk navigasi. Sumbu jarak dan elevasi memakai skala berbeda.';

    public function fetch(float $startLat, float $startLng, float $endLat, float $endLng): array
    {
        app(KkprlProposalAccessService::class)->assertApproved();
        foreach ([[$startLat, 85], [$endLat, 85], [$startLng, 180], [$endLng, 180]] as [$value, $limit]) {
            if (! is_finite($value) || abs($value) > $limit) {
                throw new InvalidSeabedProfile('Koordinat lintasan tidak valid. Gunakan lintang -85 sampai 85 dan bujur -180 sampai 180.');
            }
        }
        $length = $this->distance($startLat, $startLng, $endLat, $endLng);
        if ($length < 10 || $length > 100000) {
            throw new InvalidSeabedProfile('Pilih dua titik berbeda dengan panjang lintasan antara 10 meter dan 100 kilometer.');
        }
        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(20)
                ->withOptions(['allow_redirects' => false, 'stream' => true, 'read_timeout' => 20])->get(self::ENDPOINT, [
                    'start_latitude' => $startLat, 'start_longitude' => $startLng,
                    'end_latitude' => $endLat, 'end_longitude' => $endLng, 'format' => 'json',
                ]);
        } catch (ConnectionException $exception) {
            throw new InvalidSeabedProfile('GMRT belum dapat dihubungi. Coba lagi beberapa saat; grafik dan narasi sebelumnya tetap tersedia.');
        }
        if (! $response->successful()) {
            throw new InvalidSeabedProfile('Data GMRT belum tersedia. Coba lagi beberapa saat atau gunakan gambar penampang yang Anda miliki.');
        }
        // Read a bounded response before decoding; a remote grid or error page must not exhaust hosting memory.
        $body = $response->toPsrResponse()->getBody();
        $binary = '';
        try {
            if ($body->isSeekable()) {
                $body->rewind();
            }
            while (! $body->eof() && strlen($binary) <= 262144) {
                $chunk = $body->read(min(8192, 262145 - strlen($binary)));
                if ($chunk === '' && ! $body->eof()) {
                    throw new \RuntimeException('Incomplete profile stream.');
                }
                $binary .= $chunk;
            }
        } catch (\RuntimeException $exception) {
            throw new InvalidSeabedProfile('Data GMRT belum selesai diterima. Coba lagi; grafik dan narasi sebelumnya tetap tersedia.');
        } finally {
            $body->close();
        }
        if (strlen($binary) > 262144) {
            throw new InvalidSeabedProfile('Respons GMRT terlalu besar. Pilih lintasan yang lebih pendek atau coba lagi.');
        }
        try {
            $rows = json_decode($binary, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidSeabedProfile('Format data GMRT belum dapat dibaca. Grafik dan narasi sebelumnya tetap tersedia.');
        }
        if (! is_array($rows) || ! array_is_list($rows) || count($rows) < 2 || count($rows) > 1000) {
            throw new InvalidSeabedProfile('Jumlah sampel GMRT tidak memadai atau format respons berubah.');
        }
        $samples = [];
        $previous = -1.0;
        foreach ($rows as $row) {
            if (! is_array($row) || ! array_is_list($row) || count($row) !== 4) {
                throw new InvalidSeabedProfile('Susunan kolom GMRT tidak sesuai.');
            }
            foreach ([0, 1, 3] as $index) {
                if ((! is_int($row[$index]) && ! is_float($row[$index])) || ! is_finite((float) $row[$index])) {
                    throw new InvalidSeabedProfile('Koordinat atau jarak sampel GMRT tidak valid.');
                }
            }
            [$lat, $lng, $elevation, $distance] = $row;
            if (abs($lat) > 85 || abs($lng) > 180 || $distance < 0 || $distance <= $previous
                || ($elevation !== null && ((! is_int($elevation) && ! is_float($elevation))
                    || ! is_finite((float) $elevation) || $elevation < -12000 || $elevation > 9000))) {
                throw new InvalidSeabedProfile('Elevasi atau urutan jarak GMRT tidak valid.');
            }
            $fromStart = $this->distance($startLat, $startLng, $lat, $lng);
            $toEnd = $this->distance($lat, $lng, $endLat, $endLng);
            $tolerance = max(50, $length * 0.02);
            if (abs($fromStart - $distance) > $tolerance || abs($fromStart + $toEnd - $length) > $tolerance) {
                throw new InvalidSeabedProfile('Sampel GMRT tidak sesuai lintasan yang dipilih.');
            }
            $samples[] = ['latitude' => (float) $lat, 'longitude' => (float) $lng,
                'elevation_m' => $elevation === null ? null : (float) $elevation, 'distance_m' => (float) $distance];
            $previous = $distance;
        }
        $first = $samples[0];
        $last = $samples[array_key_last($samples)];
        if (abs($first['latitude'] - $startLat) > 0.00015 || abs($first['longitude'] - $startLng) > 0.00015
            || abs($last['latitude'] - $endLat) > 0.00015 || abs($last['longitude'] - $endLng) > 0.00015
            || abs($first['distance_m']) > 0.1 || abs($last['distance_m'] - $length) > max(2, $length * 0.02)) {
            throw new InvalidSeabedProfile('Titik awal atau akhir respons GMRT tidak sesuai permintaan.');
        }
        $valid = array_values(array_filter($samples, fn ($sample) => $sample['elevation_m'] !== null));
        if (count($valid) < 2) {
            throw new InvalidSeabedProfile('Elevasi GMRT belum memadai pada lintasan ini. Geser lintasan atau gunakan bukti lain.');
        }

        return ['method' => 'gmrt-profile-v1', 'start' => ['latitude' => $startLat, 'longitude' => $startLng],
            'end' => ['latitude' => $endLat, 'longitude' => $endLng], 'length_m' => $last['distance_m'],
            'samples' => $samples, 'valid_samples' => count($valid), 'missing_samples' => count($samples) - count($valid),
            'nonnegative_samples' => count(array_filter($valid, fn ($sample) => $sample['elevation_m'] >= 0)),
            'minimum_elevation_m' => min(array_column($valid, 'elevation_m')),
            'maximum_elevation_m' => max(array_column($valid, 'elevation_m')),
            'response_sha256' => hash('sha256', $binary), 'retrieved_at' => now()->toIso8601String(),
            'limitations' => self::LIMITATIONS];
    }

    private function distance(float $aLat, float $aLng, float $bLat, float $bLng): float
    {
        $h = sin(deg2rad($bLat - $aLat) / 2) ** 2
            + cos(deg2rad($aLat)) * cos(deg2rad($bLat)) * sin(deg2rad($bLng - $aLng) / 2) ** 2;

        return 6371008.8 * 2 * asin(sqrt(min(1.0, max(0.0, $h))));
    }

    public function report(array $profile): string
    {
        $number = fn ($value) => number_format((float) $value, 1, ',', '.');
        $point = fn ($value) => number_format((float) $value['latitude'], 6, '.', '').', '.number_format((float) $value['longitude'], 6, '.', '');
        $text = 'Penampang sekunder dasar perairan dibuat dari GMRT sepanjang lintasan A ('.$point($profile['start']).') ke B ('
            .$point($profile['end']).') dalam koordinat WGS84. Panjang lintasan menurut layanan adalah '.$number($profile['length_m'])
            .' m, dengan '.$profile['valid_samples'].' sampel elevasi valid dari '.count($profile['samples']).' titik. '
            .'Elevasi terendah sampel adalah '.$number($profile['minimum_elevation_m']).' m dan tertinggi '.$number($profile['maximum_elevation_m'])
            .' m menurut acuan data sumber; angka negatif dipertahankan sebagai elevasi negatif, bukan kedalaman terukur dari muka air saat ini.';
        if ($profile['nonnegative_samples'] > 0) {
            $text .= ' Sebanyak '.$profile['nonnegative_samples'].' sampel mempunyai elevasi nol atau positif; lintasan dapat melintasi daratan atau tepian pantai. Nilai ini tidak dinyatakan sebagai kedalaman laut.';
        }
        if ($profile['missing_samples'] > 0) {
            $text .= ' Sebanyak '.$profile['missing_samples'].' titik tidak memiliki elevasi; bagian kosong tidak diinterpolasi atau dihubungkan sebagai dasar perairan.';
        }

        return $text."\n\n".self::LIMITATIONS;
    }

    /** Render only validated server samples, with gaps and positive elevations preserved. */
    public function png(array $profile): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new InvalidSeabedProfile('Pembuatan grafik memerlukan ekstensi GD pada PHP. Gunakan unggah gambar atau minta hosting mengaktifkan GD.');
        }
        $image = imagecreatetruecolor(1100, 580);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 30, 41, 59);
        $grid = imagecolorallocate($image, 226, 232, 240);
        $blue = imagecolorallocate($image, 37, 99, 235);
        imagefill($image, 0, 0, $white);
        $min = min(0, $profile['minimum_elevation_m']);
        $max = max(0, $profile['maximum_elevation_m']);
        $padding = max(1, ($max - $min) * 0.08);
        $min -= $padding;
        $max += $padding;
        $x = fn ($distance) => (int) round(100 + 930 * $distance / $profile['length_m']);
        $y = fn ($height) => (int) round(90 + 340 * ($max - $height) / ($max - $min));
        imagestring($image, 5, 100, 25, 'Penampang dasar perairan A - B (data sekunder GMRT)', $ink);
        imagestring($image, 3, 100, 51, 'Elevasi (m; acuan sumber belum diketahui)', $ink);
        for ($i = 0; $i <= 4; $i++) {
            $distance = $profile['length_m'] * $i / 4;
            $elevation = $min + ($max - $min) * $i / 4;
            imageline($image, 100, $y($elevation), 1030, $y($elevation), $grid);
            imagestring($image, 3, 20, $y($elevation) - 7, number_format($elevation, 1, '.', ''), $ink);
            imageline($image, $x($distance), 90, $x($distance), 430, $grid);
            imagestring($image, 3, $x($distance) - 20, 442, number_format($distance, 1, '.', ''), $ink);
        }
        imageline($image, 100, $y(0), 1030, $y(0), $ink);
        imagesetthickness($image, 3);
        $previous = null;
        foreach ($profile['samples'] as $sample) {
            if ($sample['elevation_m'] === null) {
                $previous = null;

                continue;
            }
            $current = [$x($sample['distance_m']), $y($sample['elevation_m'])];
            if ($previous !== null) {
                imageline($image, $previous[0], $previous[1], $current[0], $current[1], $blue);
            }
            imagefilledellipse($image, $current[0], $current[1], 5, 5, $blue);
            $previous = $current;
        }
        imagestring($image, 3, 100, 464, 'A', $ink);
        imagestring($image, 3, 450, 464, 'Jarak sepanjang lintasan (m)', $ink);
        imagestring($image, 3, 1024, 464, 'B', $ink);
        imagestring($image, 3, 100, 488, 'Skala sumbu berbeda. Bukan survei / bukan untuk navigasi. Celah data tidak diinterpolasi.', $ink);
        imagestring($image, 3, 100, 508, 'GMRT Synthesis - CC BY 4.0 - https://www.gmrt.org/ - Grafik diolah aplikasi', $ink);
        $point = fn ($p) => number_format($p['latitude'], 6, '.', '').', '.number_format($p['longitude'], 6, '.', '');
        imagestring($image, 3, 100, 528, 'WGS84 - A ('.$point($profile['start']).') - B ('.$point($profile['end']).')', $ink);
        imagestring($image, 3, 100, 548, 'Diakses: '.$profile['retrieved_at'], $ink);
        ob_start();
        try {
            imagepng($image);

            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }
    }
}
