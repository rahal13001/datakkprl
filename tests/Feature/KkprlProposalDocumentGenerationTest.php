<?php

namespace Tests\Feature;

use App\Domain\Kkprl\PdfAttachmentRenderFailed;
use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposalDocument;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Smalot\PdfParser\Parser;
use Tests\TestCase;
use ZipArchive;

class KkprlProposalDocumentGenerationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function word_and_pdf_use_the_same_locked_snapshot_and_manifest(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : ($field === 'site_plan_description' ? 'Uraian site plan panjang.' : 'Terisi'));
            }
        }
        $proposal->update(['payload' => $payload]);
        app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->createWithContent('support.pdf', Pdf::loadHTML('<p>PDF pendukung</p>')->output()),
            ['placement' => 'appendix', 'caption' => 'PDF pendukung'],
        );
        $attachment = app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->image('site-plan.png', 600, 300),
            ['placement' => 'inline', 'anchor_key' => 'uraian site plan panjang', 'caption' => 'Site plan'],
        );
        $webpAttachment = app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->image('photo.webp', 20, 40),
            ['placement' => 'appendix', 'caption' => 'Foto WebP'],
        );

        $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1');

        $this->assertCount(2, $documents);
        $this->assertSame($documents[0]->snapshot_hash, $documents[1]->snapshot_hash);
        $this->assertSame($documents[0]->attachment_manifest_hash, $documents[1]->attachment_manifest_hash);
        $this->assertSame('generated', $documents[0]->generation_status);
        Storage::disk('kkprl_private')->assertExists($documents[0]->storage_path);
        Storage::disk('kkprl_private')->assertExists($documents[1]->storage_path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('kkprl_private')->path($documents[0]->storage_path)));
        $this->assertGreaterThan(0, $zip->numFiles);
        $xml = $zip->getFromName('word/document.xml');
        $relationshipsXml = $zip->getFromName('word/_rels/document.xml.rels');
        $image = $zip->getFromName('word/media/image1.png');
        $normalizedWebp = $zip->getFromName('word/media/image2.png');
        $zip->close();

        $xmlDocument = new \DOMDocument;
        $this->assertTrue($xmlDocument->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS));
        $this->assertIsString($relationshipsXml);
        $this->assertIsString($xml);
        $relationshipsDocument = new \DOMDocument;
        $this->assertTrue($relationshipsDocument->loadXML($relationshipsXml, LIBXML_NONET | LIBXML_NOBLANKS));

        $this->assertStringContainsString('Uraian site plan panjang.', $xml);
        $this->assertStringContainsString('RENCANA BANGUNAN DAN INSTALASI LAUT', $xml);
        $this->assertStringContainsString('TAHUN '.$proposal->created_at->year, $xml);
        $this->assertStringContainsString('INFORMASI PEMOHON', $xml);
        $this->assertStringContainsString('RENCANA KEGIATAN', $xml);
        $this->assertStringContainsString('JENIS KEGIATAN', $xml);
        $this->assertStringContainsString('RENCANA TAPAK/ SITE PLAN', $xml);
        $this->assertStringContainsString('PETA LOKASI', $xml);
        $this->assertStringContainsString('Nama pemohon:', $xml);
        $this->assertStringContainsString('Narasi rencana tapak/site plan:', $xml);
        $this->assertStringNotContainsString('Applicant Name:', $xml);
        $pageBreakPosition = strpos($xml, '<w:br w:type="page"/>');
        $informationPosition = strpos($xml, 'INFORMASI PEMOHON');
        $this->assertIsInt($pageBreakPosition);
        $this->assertIsInt($informationPosition);
        $this->assertLessThan($informationPosition, $pageBreakPosition);
        $this->assertStringNotContainsString('6281234567890', $xml);
        $this->assertStringContainsString('Lampiran Bab 1-1', $xml);
        $this->assertStringContainsString('<wp:extent cx="5715000" cy="2857500"/>', $xml);
        $this->assertStringContainsString('<wp:extent cx="190500" cy="381000"/>', $xml);
        foreach (file(base_path('tests/Fixtures/kkprl/bag-1.golden.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $marker) {
            $this->assertStringContainsString(trim($marker), strip_tags($xml));
        }
        $this->assertLessThan(
            strpos($xml, 'LAMPIRAN BAB'),
            strpos($xml, 'Lampiran Bab 1-1'),
        );
        $pdf = $documents->firstWhere('format', 'pdf');
        $pdfBinary = Storage::disk('kkprl_private')->get($pdf->storage_path);
        $pdfPages = (new Parser)->parseContent($pdfBinary)->getPages();
        $pdfText = implode("\n", array_map(fn ($page): string => $page->getText(), $pdfPages));
        foreach (file(base_path('tests/Fixtures/kkprl/bag-1.golden.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $marker) {
            $this->assertStringContainsString(mb_strtolower(trim($marker)), mb_strtolower($pdfText));
        }
        $this->assertStringNotContainsString('6281234567890', $pdfText);
        $normalizedPdfText = preg_replace('/\s+/', ' ', $pdfText) ?: $pdfText;
        $this->assertStringContainsString('RENCANA BANGUNAN DAN INSTALASI LAUT', $normalizedPdfText);
        $this->assertStringContainsString('TAHUN '.$proposal->created_at->year, $normalizedPdfText);
        $this->assertStringContainsString('INFORMASI PEMOHON', $normalizedPdfText);
        $this->assertStringContainsString('RENCANA KEGIATAN', $normalizedPdfText);
        $this->assertStringContainsString('JENIS KEGIATAN', $normalizedPdfText);
        $this->assertStringContainsString('RENCANA TAPAK/ SITE PLAN', $normalizedPdfText);
        $this->assertStringContainsString('PETA LOKASI', $normalizedPdfText);
        $this->assertStringContainsString('Nama pemohon', $normalizedPdfText);
        $this->assertStringContainsString('Narasi rencana tapak/site plan', $normalizedPdfText);
        $this->assertStringNotContainsString('Applicant Name:', $normalizedPdfText);
        $this->assertStringContainsString('PROPOSAL PERMOHONAN', $pdfPages[0]->getText());
        $this->assertStringNotContainsString('Nama pemohon', $pdfPages[0]->getText());
        $this->assertNotSame('', trim($pdfPages[1]->getText()));
        $this->assertStringContainsString('/MediaBox [0.000 0.000 595.280 841.890]', $pdfBinary);
        $this->assertNotFalse($image);
        $this->assertNotFalse($normalizedWebp);
        $this->assertSame('image/webp', $webpAttachment->mime_type);
        $this->assertStringEndsWith('.webp', $webpAttachment->storage_path);
        Storage::disk('kkprl_private')->assertExists($webpAttachment->storage_path);
        $this->assertNotNull($attachment->fresh());

        $retry = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1');
        $this->assertCount(2, $retry);
        $this->assertSame($documents->pluck('id')->sort()->values()->all(), $retry->pluck('id')->sort()->values()->all());
        $this->assertDatabaseCount('kkprl_proposal_documents', 2);

        $this->expectException(\LogicException::class);
        $documents[0]->update(['snapshot_payload_encrypted' => ['tampered' => true]]);
    }

    #[Test]
    public function incomplete_chapter_does_not_create_document(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);

        $this->expectException(ValidationException::class);
        app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal, 'bag-1');
        $this->assertDatabaseCount('kkprl_proposal_documents', 0);
    }

    #[Test]
    public function generated_document_cannot_be_persisted_on_a_non_private_disk(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);

        $this->expectException(\LogicException::class);
        KkprlProposalDocument::create([
            'proposal_id' => $proposal->id,
            'chapter' => 'bag-1',
            'format' => 'pdf',
            'generation_scope' => 'chapter',
            'template_version' => 'test',
            'snapshot_hash' => str_repeat('a', 64),
            'attachment_manifest_hash' => str_repeat('b', 64),
            'storage_disk' => 'public',
            'generation_status' => 'generated',
        ]);
    }

    #[Test]
    public function generated_document_snapshot_cannot_be_deleted(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $document = KkprlProposalDocument::create([
            'proposal_id' => $proposal->id,
            'chapter' => 'bag-1',
            'format' => 'pdf',
            'generation_scope' => 'chapter',
            'template_version' => 'test',
            'snapshot_hash' => str_repeat('a', 64),
            'attachment_manifest_hash' => str_repeat('b', 64),
            'storage_path' => 'kkprl/test.pdf',
            'generation_status' => 'generated',
        ]);

        $this->expectException(\LogicException::class);
        $document->delete();
    }

    #[Test]
    public function generated_document_storage_path_cannot_be_changed(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $document = KkprlProposalDocument::create([
            'proposal_id' => $proposal->id,
            'chapter' => 'bag-1',
            'format' => 'pdf',
            'generation_scope' => 'chapter',
            'template_version' => 'test',
            'snapshot_hash' => str_repeat('a', 64),
            'attachment_manifest_hash' => str_repeat('b', 64),
            'storage_path' => 'kkprl/immutable.pdf',
            'generation_status' => 'generated',
        ]);

        $this->expectException(\LogicException::class);
        $document->update(['storage_path' => 'kkprl/tampered.pdf']);
    }

    #[Test]
    public function retry_does_not_overwrite_an_existing_generated_artifact(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (ProposalProgress::requiredFields('bag-1') as $field) {
            $payload[$field] = match ($field) {
                'latitude' => '-6.2',
                'longitude' => '106.8',
                default => 'Terisi',
            };
        }
        $proposal->update(['payload' => $payload]);

        $service = app(KkprlProposalDocumentService::class);
        $document = $service->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf'])->firstOrFail();
        $artifact = Storage::disk('kkprl_private')->get($document->storage_path);

        $retry = $service->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf'])->firstOrFail();

        $this->assertSame($document->id, $retry->id);
        $this->assertSame($artifact, Storage::disk('kkprl_private')->get($document->storage_path));
    }

    #[Test]
    public function generation_fails_when_an_attachment_binary_is_missing(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (ProposalProgress::requiredFields('bag-1') as $field) {
            $payload[$field] = match ($field) {
                'latitude' => '-6.2',
                'longitude' => '106.8',
                default => 'Terisi',
            };
        }
        $proposal->update(['payload' => $payload]);
        $attachment = app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->image('missing.png'),
        );
        Storage::disk('kkprl_private')->delete($attachment->storage_path);

        $this->expectException(PdfAttachmentRenderFailed::class);
        app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf']);
    }

    #[Test]
    public function snapshot_rejects_a_legacy_attachment_on_a_non_private_disk(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (ProposalProgress::requiredFields('bag-1') as $field) {
            $payload[$field] = match ($field) {
                'latitude' => '-6.2',
                'longitude' => '106.8',
                default => 'Terisi',
            };
        }
        $proposal->update(['payload' => $payload]);

        \Illuminate\Support\Facades\DB::table('kkprl_proposal_attachments')->insert([
            'proposal_id' => $proposal->id,
            'chapter' => 'bag-1',
            'attachment_role' => 'supporting',
            'placement' => 'appendix',
            'original_name' => 'legacy.png',
            'storage_disk' => 'public',
            'storage_path' => 'legacy/attachment.png',
            'mime_type' => 'image/png',
            'size' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1');
    }

    #[Test]
    public function attachment_manifest_change_creates_a_new_immutable_document_version(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (ProposalProgress::requiredFields('bag-1') as $field) {
            $payload[$field] = match ($field) {
                'latitude' => '-6.2',
                'longitude' => '106.8',
                'includes_reclamation' => false,
                'land_relation' => 'not_adjacent',
                'has_existing_permits' => false,
                default => 'Terisi',
            };
        }
        $proposal->update(['payload' => $payload]);

        app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->image('first.png', 20, 20),
            ['placement' => 'appendix', 'caption' => 'Versi pertama'],
        );
        $first = app(KkprlProposalDocumentService::class)
            ->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf'])
            ->first();

        app(KkprlProposalAttachmentService::class)->store(
            $proposal->fresh(),
            'bag-1',
            UploadedFile::fake()->image('second.png', 30, 30),
            ['placement' => 'appendix', 'caption' => 'Versi kedua'],
        );
        $second = app(KkprlProposalDocumentService::class)
            ->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf'])
            ->first();

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($first->snapshot_hash, $second->snapshot_hash);
        $this->assertNotSame($first->attachment_manifest_hash, $second->attachment_manifest_hash);
        $this->assertNotSame($first->storage_path, $second->storage_path);
        $this->assertDatabaseCount('kkprl_proposal_documents', 2);
        $this->assertSame(
            $second->attachment_manifest_hash,
            $second->fresh()->attachment_manifest_hash,
        );
        Storage::disk('kkprl_private')->assertExists($first->storage_path);
        Storage::disk('kkprl_private')->assertExists($second->storage_path);
    }

    #[Test]
    public function configured_pdf_converter_renders_scanned_pdf_pages_for_both_formats(): void
    {
        Storage::fake('kkprl_private');
        config([
            'kkprl.pdf_renderer.binary' => PHP_BINARY,
            'kkprl.pdf_renderer.format' => 'png',
            'kkprl.pdf_renderer.prepend_arguments' => [
                base_path('tests/Fixtures/kkprl/fake-pdftoppm.php'),
            ],
        ]);
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        $proposal->update(['payload' => $payload]);
        $attachment = app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->createWithContent('scan.pdf', "%PDF-1.7\nscan"),
        );
        $original = Storage::disk('kkprl_private')->get($attachment->storage_path);

        $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('kkprl_private')->path($documents->firstWhere('format', 'docx')->storage_path)));
        $xml = $zip->getFromName('word/document.xml');
        $image = $zip->getFromName('word/media/image1.png');
        $zip->close();

        $this->assertStringContainsString('Halaman PDF 1', $xml);
        $this->assertNotFalse($image);
        $this->assertSame($original, Storage::disk('kkprl_private')->get($attachment->storage_path));
    }

    #[Test]
    public function scanned_pdf_without_renderer_fails_generation_instead_of_using_placeholder_text(): void
    {
        Storage::fake('kkprl_private');
        config(['kkprl.pdf_renderer.binary' => base_path('missing-pdf-renderer')]);
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        $proposal->update(['payload' => $payload]);
        app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->createWithContent('scan.pdf', "%PDF-1.7\nscan"),
        );

        try {
            app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1');
            $this->fail('Scanned PDF without a renderer must fail document generation.');
        } catch (PdfAttachmentRenderFailed) {
            $this->assertDatabaseHas('kkprl_proposal_documents', [
                'proposal_id' => $proposal->id,
                'chapter' => 'bag-1',
                'format' => 'docx',
                'generation_status' => 'failed',
                'error_code' => 'render_failed',
            ]);
        }
    }

    #[Test]
    public function configured_real_pdf_renderer_renders_a_valid_image_only_pdf(): void
    {
        Storage::fake('kkprl_private');
        $binary = config('kkprl.pdf_renderer.binary');
        if (! is_string($binary) || ! is_file($binary)) {
            $this->markTestSkipped('A configured PDF renderer binary is not available.');
        }
        config(['kkprl.pdf_renderer.format' => 'bmp']);

        $image = UploadedFile::fake()->image('scan-source.png', 40, 40);
        $imageBinary = file_get_contents($image->getRealPath());
        $this->assertIsString($imageBinary);
        $sourcePdf = Pdf::loadHTML('<img src="data:image/png;base64,'.base64_encode($imageBinary).'">')->output();
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        $proposal->update(['payload' => $payload]);
        app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->createWithContent('scan.pdf', $sourcePdf),
        );

        $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('kkprl_private')->path($documents->firstWhere('format', 'docx')->storage_path)));
        $xml = $zip->getFromName('word/document.xml');
        $renderedImage = $zip->getFromName('word/media/image1.png');
        $zip->close();

        $this->assertStringContainsString('Halaman PDF 1', $xml);
        $this->assertNotFalse($renderedImage);
        $this->assertNotSame('', $renderedImage);
    }

    #[Test]
    public function schedule_rows_render_as_a_repeatable_table_in_both_formats(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        $payload['includes_reclamation'] = true;
        $payload['bag-4'] = array_fill_keys(ProposalProgress::requiredFields('bag-4'), 'Terisi');
        $payload['bag-4']['reclamation_schedule_rows'] = [[
            'activity' => 'Pengerukan',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-01',
            'notes' => 'Tahap awal',
            'risiko' => 'Rendah',
        ]];
        $proposal->update(['payload' => $payload]);

        $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-4');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('kkprl_private')->path($documents->firstWhere('format', 'docx')->storage_path)));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('<w:tblHeader/>', $xml);
        $this->assertStringContainsString('w:orient="landscape"', $xml);
        $this->assertStringContainsString('Pengerukan', $xml);
        $pdf = $documents->firstWhere('format', 'pdf');
        $pdfBinary = Storage::disk('kkprl_private')->get($pdf->storage_path);
        $pdfText = (new Parser)->parseContent($pdfBinary)->getText();
        $this->assertStringContainsString('Pengerukan', $pdfText);
        $this->assertStringContainsString('Tahap awal', $pdfText);
        $this->assertStringContainsString('/MediaBox [0.000 0.000 841.890 595.280]', $pdfBinary);
    }

    #[Test]
    public function official_template_xml_numbering_matches_the_golden_catalog(): void
    {
        $templates = [
            'bag-1' => 'docs/template/Bag 1. Proposal KKPRL_RENCANA BANGUNAN DAN INSTALASI LAUT.docx',
            'bag-2' => 'docs/template/Bag 2. Proposal KKPRL_INFORMASI PEMANFAATAN RUANG LAUT.docx',
            'bag-3' => 'docs/template/Bag 3. Proposal KKPRL_KONDISI TERKINI PERAIRAN DAN SEKITARNYA.docx',
            'bag-4' => 'docs/template/Bag 4. Proposal KKPRL_PERSYARATAN REKLAMASI.docx',
            'bag-5' => 'docs/template/Bag 5. Proposal KKPRL_PERIZINAN LAINNYA.docx',
        ];
        $headings = json_decode(
            (string) file_get_contents(base_path('tests/Fixtures/kkprl/official-sections.golden.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $sectionNumbers = json_decode(
            (string) file_get_contents(base_path('tests/Fixtures/kkprl/official-section-numbering.golden.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        foreach ($templates as $chapter => $template) {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open(base_path($template)) === true);
            $documentXml = $zip->getFromName('word/document.xml');
            $numberingXml = $zip->getFromName('word/numbering.xml');
            $zip->close();
            $this->assertIsString($documentXml);
            $this->assertIsString($numberingXml);

            $document = new \DOMDocument;
            $this->assertTrue($document->loadXML($documentXml, LIBXML_NONET | LIBXML_NOBLANKS));
            $numbering = new \DOMDocument;
            $this->assertTrue($numbering->loadXML($numberingXml, LIBXML_NONET | LIBXML_NOBLANKS));

            $documentXPath = new \DOMXPath($document);
            $documentXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $numberingXPath = new \DOMXPath($numbering);
            $numberingXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $paragraphs = $documentXPath->query('//w:body/w:p');
            $this->assertNotFalse($paragraphs);

            foreach ($headings[$chapter] as $index => $heading) {
                $matched = null;
                foreach ($paragraphs as $paragraph) {
                    $paragraphText = trim(preg_replace('/\s+/', ' ', $paragraph->textContent) ?: '');
                    $compactParagraphText = preg_replace('/\s+/', '', $paragraphText) ?: '';
                    $compactHeading = preg_replace('/\s+/', '', $heading) ?: '';
                    if ($paragraphText === $heading || str_contains($paragraphText, $heading) || str_contains($compactParagraphText, $compactHeading)) {
                        $matched = $paragraph;
                        break;
                    }
                }
                $this->assertNotNull($matched, $chapter.' heading: '.$heading);

                $numId = $matched === null
                    ? ''
                    : trim((string) $documentXPath->evaluate('string(./w:pPr/w:numPr/w:numId/@w:val)', $matched));
                $expectedNumber = $sectionNumbers[$chapter][$index] ?? null;
                if ($expectedNumber === null) {
                    $this->assertSame('', $numId, $chapter.' heading must not be numbered: '.$heading);

                    continue;
                }

                $this->assertNotSame('', $numId, $chapter.' heading must have numPr: '.$heading);
                $abstractId = trim((string) $numberingXPath->evaluate(
                    'string(//w:num[@w:numId="'.$numId.'"]/w:abstractNumId/@w:val)',
                ));
                $format = trim((string) $numberingXPath->evaluate(
                    'string(//w:abstractNum[@w:abstractNumId="'.$abstractId.'"]/w:lvl[@w:ilvl="0"]/w:numFmt/@w:val)',
                ));
                $this->assertSame('upperRoman', $format, $chapter.' heading format: '.$heading);
            }
        }
    }

    #[Test]
    public function every_active_chapter_exports_with_its_official_title_in_both_formats(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];

        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }

        $payload['includes_reclamation'] = true;
        foreach (ProposalProgress::requiredFields('bag-4') as $field) {
            $payload[$field] = $field === 'reclamation_schedule_rows'
                ? [['activity' => 'Pengerukan', 'start_date' => '2026-01-01', 'end_date' => '2026-02-01', 'notes' => 'Tahap awal']]
                : 'Terisi';
        }

        $payload['land_relation'] = 'adjacent';
        $payload['has_existing_permits'] = true;
        foreach (['bag-5-land', 'bag-5-permits'] as $section) {
            foreach (ProposalProgress::requiredFields($section) as $field) {
                $payload[$field] = 'Terisi';
            }
        }
        $proposal->update(['payload' => $payload]);

        $titles = [
            'bag-1' => 'RENCANA BANGUNAN DAN INSTALASI LAUT',
            'bag-2' => 'INFORMASI PEMANFAATAN RUANG LAUT',
            'bag-3' => 'KONDISI TERKINI PERAIRAN DAN SEKITARNYA',
            'bag-4' => 'PERSYARATAN REKLAMASI',
            'bag-5' => 'PERIZINAN LAINNYA',
        ];

        $sectionHeadings = json_decode(
            (string) file_get_contents(base_path('tests/Fixtures/kkprl/official-sections.golden.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertIsArray($sectionHeadings);
        $sectionNumbers = json_decode(
            (string) file_get_contents(base_path('tests/Fixtures/kkprl/official-section-numbering.golden.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertIsArray($sectionNumbers);

        foreach ($titles as $chapter => $title) {
            $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), $chapter);

            $this->assertCount(2, $documents);

            $zip = new ZipArchive;
            $this->assertTrue($zip->open(Storage::disk('kkprl_private')->path($documents->firstWhere('format', 'docx')->storage_path)));
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertIsString($xml);
            $this->assertStringContainsString($title, $xml);
            foreach ($sectionHeadings[$chapter] as $heading) {
                $this->assertStringContainsString($heading, $xml);
            }
            if ($sectionNumbers[$chapter] !== []) {
                foreach (array_map(null, $sectionNumbers[$chapter], $sectionHeadings[$chapter]) as [$number, $heading]) {
                    $this->assertStringContainsString($number.' '.$heading, $xml);
                }
            }
            if ($chapter === 'bag-2') {
                $this->assertStringNotContainsString('I. '.$sectionHeadings[$chapter][0], $xml);
            }

            $pdf = $documents->firstWhere('format', 'pdf');
            $pdfText = (new Parser)->parseContent(Storage::disk('kkprl_private')->get($pdf->storage_path))->getText();
            $normalizedPdfText = preg_replace('/\s+/', ' ', $pdfText) ?: $pdfText;
            $this->assertStringContainsString($title, $normalizedPdfText);
            foreach ($sectionHeadings[$chapter] as $heading) {
                $this->assertStringContainsString($heading, $normalizedPdfText);
            }
            if ($sectionNumbers[$chapter] !== []) {
                foreach (array_map(null, $sectionNumbers[$chapter], $sectionHeadings[$chapter]) as [$number, $heading]) {
                    $this->assertStringContainsString($number.' '.$heading, $normalizedPdfText);
                }
            }
            if ($chapter === 'bag-2') {
                $this->assertStringNotContainsString('I. '.$sectionHeadings[$chapter][0], $normalizedPdfText);
            }
        }
    }
}
