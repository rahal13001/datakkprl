<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalDocumentPresentation;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlApplicantPortal;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalDocument;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalPdfRenderer;
use App\Services\KkprlProposalSnapshotService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Smalot\PdfParser\Parser;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;
use ZipArchive;

class KkprlDocumentLayoutTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    private function proposal(array $extra = []): KkprlProposal
    {
        Storage::fake('kkprl_private');
        Http::preventStrayRequests();
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = KkprlPayloadFixture::value($field);
            }
        }

        return $this->ownedProposal(['payload' => array_replace($payload, ['institution_name' => 'Institusi Contoh Layout'], $extra)]);
    }

    private function part(KkprlProposalDocument $document, string $part): string
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('kkprl_private')->path($document->storage_path)) === true);
        $value = $zip->getFromName($part);
        $zip->close();
        $this->assertIsString($value);

        return $value;
    }

    #[Test]
    public function word_has_real_styles_footer_and_compact_identity_tables(): void
    {
        $proposal = $this->proposal(['site_plan_description' => '<p>Paragraf satu.</p><p><strong>Paragraf dua.</strong></p>']);
        $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-1');
        $word = $documents->firstWhere('format', 'docx');
        $xml = $this->part($word, 'word/document.xml');
        $styles = $this->part($word, 'word/styles.xml');
        $rels = $this->part($word, 'word/_rels/document.xml.rels');
        $footer = $this->part($word, 'word/footer1.xml');
        foreach ([$xml, $styles, $rels, $footer] as $part) {
            $dom = new \DOMDocument;
            $this->assertTrue($dom->loadXML($part, LIBXML_NONET));
        }
        $this->assertStringContainsString('w:pStyle w:val="CoverTitle"', $xml);
        $this->assertStringContainsString('w:pStyle w:val="Heading1"', $xml);
        $this->assertStringContainsString('w:tblW', $xml);
        $this->assertStringContainsString('w:tblGrid', $xml);
        $this->assertStringContainsString('w:keepNext', $styles);
        $this->assertStringContainsString('w:widowControl', $styles);
        $this->assertStringContainsString('/styles', $rels);
        $this->assertStringContainsString('/footer', $rels);
        $this->assertStringContainsString(' PAGE ', $footer);
        $this->assertStringContainsString('<w:t xml:space="preserve">Halaman </w:t>', $footer);
        $this->assertStringNotContainsString('&lt;p&gt;', $xml);
        $this->assertStringContainsString('Paragraf satu.', $xml);
        $this->assertStringContainsString('Paragraf dua.', $xml);
        $pdfText = (new Parser)->parseContent(Storage::disk('kkprl_private')->get($documents->firstWhere('format', 'pdf')->storage_path))->getText();
        $this->assertStringNotContainsString('<p>', $pdfText);
        $this->assertStringContainsString('Paragraf dua.', $pdfText);
        Http::assertNothingSent();
    }

    #[Test]
    public function cover_of_later_chapters_uses_snapshotted_institution_and_retry_is_idempotent(): void
    {
        $proposal = $this->proposal();
        $service = app(KkprlProposalDocumentService::class);
        $first = $service->generateChapterFormats($proposal, 'bag-2');
        $this->assertStringContainsString('Institusi Contoh Layout', $this->part($first->firstWhere('format', 'docx'), 'word/document.xml'));
        $second = $service->generateChapterFormats($proposal->fresh(), 'bag-2');
        $this->assertSame($first->pluck('id')->all(), $second->pluck('id')->all());
        $proposal->update(['payload' => array_replace($proposal->payload, ['institution_name' => 'Institusi Layout Berubah'])]);
        $changed = $service->generateChapterFormats($proposal->fresh(), 'bag-2');
        $this->assertNotSame($first[0]->snapshot_hash, $changed[0]->snapshot_hash);
        $this->assertStringContainsString('Institusi Layout Berubah', $this->part($changed->firstWhere('format', 'docx'), 'word/document.xml'));
    }

    #[Test]
    public function unchanged_legacy_documents_remain_downloadable_and_regeneration_does_not_overwrite_them(): void
    {
        $proposal = $this->proposal();
        $snapshots = app(KkprlProposalSnapshotService::class);
        $legacy = $snapshots->capture($proposal, 'bag-1', null);
        $path = 'kkprl/fixture-legacy.pdf';
        $binary = Pdf::loadHTML('<p>Dokumen legacy fixture.</p>')->output();
        Storage::disk('kkprl_private')->put($path, $binary);
        $document = KkprlProposalDocument::create([
            'proposal_id' => $proposal->id, 'chapter' => 'bag-1', 'format' => 'pdf',
            'generation_scope' => 'chapter', 'template_version' => $legacy->templateVersion,
            'snapshot_hash' => $legacy->snapshotHash, 'attachment_manifest_hash' => $legacy->attachmentManifestHash,
            'snapshot_payload_encrypted' => $legacy->payload, 'attachment_manifest_encrypted' => $legacy->attachments,
            'storage_disk' => 'kkprl_private', 'storage_path' => $path,
            'generation_status' => 'generated', 'generated_at' => now(),
        ]);
        $this->get(route('kkprl.proposal.document.download', $document))->assertOk();
        $new = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-1', ['pdf'])->first();
        $this->assertNotSame($document->id, $new->id);
        $this->assertSame($legacy->snapshotHash, $document->fresh()->snapshot_hash);
        $this->assertSame($binary, Storage::disk('kkprl_private')->get($path));
        $this->get(route('kkprl.proposal.document.download', $document))->assertOk();
        $this->get(route('kkprl.proposal.document.download', $new))->assertOk();
        $this->applicant->forceFill(['approved_at' => null])->save();
        $this->get(route('kkprl.proposal.document.download', $document))->assertOk();
        $this->get(route('kkprl.proposal.document.download', $new))->assertOk();
        Livewire::test(KkprlApplicantPortal::class)
            ->assertSee('Lihat / unduh PDF')
            ->assertViewHas('documents', fn ($documents) => $documents->getCollection()->pluck('id')->sort()->values()->all() === [$document->id, $new->id])
            ->assertDontSee('Buat proposal');
        $this->assertSame($legacy->snapshotHash, $document->fresh()->snapshot_hash);
    }

    #[Test]
    public function unknown_layout_version_fails_closed(): void
    {
        $proposal = $this->proposal();
        $this->expectException(ValidationException::class);
        app(KkprlProposalSnapshotService::class)->capture($proposal, 'bag-1', 'unknown-layout');
    }

    #[Test]
    public function a_table_cell_taller_than_a_page_flows_without_clipping(): void
    {
        $data = [
            'chapter' => 'bag-4', 'chapterTitle' => 'Fixture tabel panjang', 'coverName' => 'Institusi Contoh',
            'ticket' => 'FIXTURE', 'year' => 2026, 'landscape' => true,
            'values' => ['schedule' => [[
                'kegiatan' => 'Pekerjaan', 'satu' => 'Ya', 'dua' => 'Ya', 'tiga' => 'Ya', 'empat' => 'Ya',
                'catatan' => implode("\n", array_map(fn ($n) => 'Baris '.$n, range(1, 80)))."\nBatas akhir baris.",
            ]]],
            'fieldLabels' => ['schedule' => 'Jadwal'], 'sectionLabels' => [],
            'inlineAttachments' => [], 'appendixAttachments' => [],
        ];
        $data['blocks'] = ProposalDocumentPresentation::blocks($data);
        $pdf = (new Parser)->parseContent(app(KkprlProposalPdfRenderer::class)->render($data));
        $this->assertGreaterThan(3, count($pdf->getPages()));
        $this->assertStringContainsString('Baris 1', $pdf->getText());
        $this->assertStringContainsString('Baris 80', $pdf->getText());
        $this->assertStringContainsString('Batas akhir baris.', $pdf->getText());
        foreach ($pdf->getPages() as $page) {
            foreach ($page->getDataTm() as [$position, $text]) {
                if (str_contains($text, 'Baris ') || str_contains($text, 'Batas akhir baris.')) {
                    $this->assertGreaterThan(33, (float) $position[5], 'Table text must stay above the footer.');
                }
            }
        }
    }

    #[Test]
    public function long_narrative_remains_readable_across_pdf_pages_without_raw_markup(): void
    {
        $text = implode("\n\n", array_map(fn ($n) => '<p>Paragraf '.$n.'. '.str_repeat('Kalimat laporan yang perlu tetap terbaca. ', 22).'</p>', range(1, 22)));
        // Smalot loses positioned words in justified lines; short boundary paragraphs
        // plus the paragraph count verify pagination without relying on that limitation.
        $text = '<p>Penanda pembuka narasi.</p>'.$text.'<p>Penanda penutup narasi.</p>';
        $proposal = $this->proposal(['marine_space_use_narrative' => $text]);
        $document = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-2', ['pdf'])->first();
        $pdf = (new Parser)->parseContent(Storage::disk('kkprl_private')->get($document->storage_path));
        $this->assertGreaterThan(3, count($pdf->getPages()));
        $this->assertStringContainsString('Penanda pembuka narasi.', $pdf->getText());
        $this->assertStringContainsString('Penanda penutup narasi.', $pdf->getText());
        $this->assertSame(22, substr_count($pdf->getText(), 'Paragraf '));
        $this->assertStringNotContainsString('<p>', $pdf->getText());
    }
}
