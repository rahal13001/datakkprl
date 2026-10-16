<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;

final class KkprlProposalPdfRenderer
{
    public function render(array $data): string
    {
        $pdf = Pdf::loadView('pdf.kkprl-proposal-chapter', $data)
            ->setPaper('a4', $data['landscape'] ? 'landscape' : 'portrait')->getDomPDF();
        $pdf->render();
        $pdf->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics): void {
            if ($pageNumber > 1) {
                $font = $fontMetrics->getFont('DejaVu Sans');
                $text = 'Halaman '.($pageNumber - 1);
                $width = $fontMetrics->getTextWidth($text, $font, 9);
                $canvas->text(($canvas->get_width() - $width) / 2, $canvas->get_height() - 33, $text, $font, 9, [.35, .35, .35]);
            }
        });
        if (isset($data['locationMap'])) {
            $this->appendMap($pdf, $data['locationMap']);
        }

        return $pdf->output();
    }

    /** Isolates the CPDF 3.1 adapter contract; mixed physical page sizes are regression tested. */
    private function appendMap(Dompdf $pdf, array $map): void
    {
        $canvas = $pdf->getCanvas();
        if (! $canvas instanceof CPDF) {
            throw new \RuntimeException('Location map requires the bundled CPDF adapter.');
        }
        $contentId = $canvas->new_page();
        $cpdf = $canvas->get_cpdf();
        $pageId = $cpdf->objects[$contentId]['onPage'] ?? null;
        if (! is_int($pageId) || ($cpdf->objects[$pageId]['t'] ?? '') !== 'page') {
            throw new \RuntimeException('Unsupported CPDF page structure.');
        }
        // Dompdf CSS cannot select a named landscape page. Set only the appended page MediaBox.
        $cpdf->objects[$pageId]['info']['mediaBox'] = [0, 0, 841.89, 595.28];
        $binary = $map['binary'];
        $printWidth = $map['print_width_points'] ?? 700;
        $cpdf->addPngFromBuf($binary, 'kkprl-map-'.hash('sha256', $binary), 71, 83, $printWidth, $printWidth * 1100 / 1800);
        $fontMetrics = $pdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans');
        // Canvas y() still uses the body height: express bottom coordinates explicitly.
        $canvas->text(71, $canvas->get_height() - 65, 'PETA LOKASI KEGIATAN', $font, 9, [.35, .35, .35]);
        $text = 'Halaman '.($canvas->get_page_count() - 1);
        $width = $fontMetrics->getTextWidth($text, $font, 9);
        $canvas->text((841.89 - $width) / 2, $canvas->get_height() - 33, $text, $font, 9, [.35, .35, .35]);
    }
}
