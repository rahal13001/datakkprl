<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PdfTemplateTableStylesTest extends TestCase
{
    public function test_berita_acara_pdf_styles_user_generated_tables(): void
    {
        $beritaAcara = new class {
            public ?string $nomor_berita_acara = 'BA.TEST';

            public ?string $lokasi_permohonan = 'Raja Ampat';

            public Carbon $tanggal_pelaksanaan;

            public ?string $kbli = null;

            public string $hasil_pendampingan = '<table><tr><td>Kolom</td></tr></table>';

            public Collection $attendees;

            public ?string $lampiran_peta = null;

            public array $lampiran_dokumentasi = [];

            public array $lampiran_lainnya = [];

            public function __construct()
            {
                $this->tanggal_pelaksanaan = Carbon::parse('2026-07-16');
                $this->attendees = collect();
            }

            public function getJenisPermohonanLabel(): string
            {
                return 'Persetujuan';
            }
        };

        $client = (object) [
            'ticket_number' => 'TICKET-TEST',
            'name' => 'Pemohon Test',
            'service' => (object) ['name' => 'Asistensi'],
        ];

        $html = view('pdf.berita-acara', [
            'beritaAcara' => $beritaAcara,
            'client' => $client,
            'signatureService' => null,
        ])->render();

        $this->assertStringContainsString('.hasil-content table', $html);
        $this->assertStringContainsString('border-collapse: collapse;', $html);
        $this->assertStringContainsString('border: 1px solid #000;', $html);
        $this->assertStringContainsString('<table><tr><td>Kolom</td></tr></table>', $html);
    }
}
