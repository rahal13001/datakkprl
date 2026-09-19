<?php

namespace App\Services;

use App\Domain\Kkprl\PdfAttachmentRenderFailed;
use App\Domain\Kkprl\ProposalDocumentSectionCatalog;
use App\Domain\Kkprl\ProposalFieldCatalog;
use App\Domain\Kkprl\ProposalInlineAnchorResolver;
use App\Domain\Kkprl\ProposalSnapshot;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use ZipArchive;

final class KkprlProposalDocumentService
{
    /** @var array<string, string> */
    private const CHAPTER_TITLES = [
        'bag-1' => 'RENCANA BANGUNAN DAN INSTALASI LAUT',
        'bag-2' => 'INFORMASI PEMANFAATAN RUANG LAUT',
        'bag-3' => 'KONDISI TERKINI PERAIRAN DAN SEKITARNYA',
        'bag-4' => 'PERSYARATAN REKLAMASI',
        'bag-5' => 'PERIZINAN LAINNYA',
    ];

    public function __construct(
        private readonly KkprlProposalSnapshotService $snapshots,
        private readonly ProposalInlineAnchorResolver $anchors,
    ) {}

    private function disk(): string
    {
        return (string) config('kkprl.storage_disk', 'kkprl_private');
    }

    /** @return Collection<int, KkprlProposalDocument> */
    public function generateChapterFormats(
        KkprlProposal $proposal,
        string $chapter,
        array $formats = ['docx', 'pdf'],
    ): Collection {
        $snapshot = $this->snapshots->capture($proposal, $chapter);
        $documents = collect();

        foreach (array_values(array_unique($formats)) as $format) {
            if (! in_array($format, ['docx', 'pdf'], true)) {
                continue;
            }

            $documents->push($this->generateOne($proposal, $snapshot, $format));
        }

        return $documents;
    }

    private function generateOne(
        KkprlProposal $proposal,
        ProposalSnapshot $snapshot,
        string $format,
    ): KkprlProposalDocument {
        $document = KkprlProposalDocument::query()->firstOrNew([
            'proposal_id' => $proposal->id,
            'chapter' => $snapshot->chapter,
            'format' => $format,
            'snapshot_hash' => $snapshot->snapshotHash,
            'attachment_manifest_hash' => $snapshot->attachmentManifestHash,
        ]);
        if (! $document->exists) {
            $document->fill([
                'generation_scope' => 'chapter',
                'template_version' => $snapshot->templateVersion,
                'attachment_manifest_hash' => $snapshot->attachmentManifestHash,
                'snapshot_payload_encrypted' => $snapshot->payload,
                'attachment_manifest_encrypted' => $snapshot->attachments,
                'storage_disk' => $this->disk(),
            ]);
        }

        if ($document->exists
            && $document->generation_status === 'generated'
            && filled($document->storage_path)
            && $document->storage_disk === $this->disk()
            && Storage::disk($this->disk())->exists($document->storage_path)) {
            return $document->fresh();
        }

        $document->fill([
            'storage_disk' => $this->disk(),
            'generation_status' => 'generating',
            'error_code' => null,
        ]);
        $document->save();

        try {
            $binary = $format === 'pdf'
                ? $this->renderPdf($snapshot)
                : $this->renderDocx($snapshot);
            $path = "kkprl/proposals/{$proposal->root_proposal_id}/{$proposal->id}/documents/{$snapshot->chapter}-{$snapshot->snapshotHash}-{$snapshot->attachmentManifestHash}.{$format}";

            if (! Storage::disk($this->disk())->put($path, $binary)) {
                throw new \RuntimeException('Unable to store generated document.');
            }
            $document->fill([
                'storage_path' => $path,
                'generation_status' => 'generated',
                'generated_at' => now(),
            ]);
            $document->save();

            return $document->fresh();
        } catch (\Throwable $exception) {
            $document->update([
                'generation_status' => 'failed',
                'error_code' => 'render_failed',
            ]);
            throw $exception;
        }
    }

    private function renderPdf(ProposalSnapshot $snapshot): string
    {
        $data = $this->viewData($snapshot);
        $orientation = $this->requiresLandscape($data) ? 'landscape' : 'portrait';

        return Pdf::loadView('pdf.kkprl-proposal-chapter', $data)
            ->setPaper('a4', $orientation)
            ->output();
    }

    /** @param array<string, mixed> $data */
    private function requiresLandscape(array $data): bool
    {
        foreach ($data['values'] ?? [] as $value) {
            if (! $this->isTableValue($value)) {
                continue;
            }

            if (count(array_keys($value[0] ?? [])) > 4) {
                return true;
            }
        }

        return false;
    }

    private function renderDocx(ProposalSnapshot $snapshot): string
    {
        $data = $this->viewData($snapshot);
        $relationships = [];
        $media = [];
        $documentXml = $this->docxDocumentXml($snapshot, $data, $relationships, $media);
        $relsXml = $this->docxRelationshipsXml($relationships);
        $contentTypesXml = $this->docxContentTypesXml($media);
        $temporary = tempnam(sys_get_temp_dir(), 'kkprl-docx-');

        if ($temporary === false) {
            throw new \RuntimeException('Unable to create DOCX temporary file.');
        }

        $zip = new ZipArchive;
        if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($temporary);
            throw new \RuntimeException('Unable to create DOCX package.');
        }
        $zip->addFromString('[Content_Types].xml', $contentTypesXml);
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->addFromString('word/_rels/document.xml.rels', $relsXml);
        $zip->addFromString('word/styles.xml', $this->docxStylesXml());

        foreach ($media as $item) {
            $zip->addFromString($item['path'], $item['binary']);
        }

        $zip->close();
        $binary = file_get_contents($temporary);
        @unlink($temporary);

        if (! is_string($binary)) {
            throw new \RuntimeException('Unable to read generated DOCX.');
        }

        return $binary;
    }

    /** @return array<string, mixed> */
    private function viewData(ProposalSnapshot $snapshot): array
    {
        $payload = $snapshot->payload['payload'] ?? [];
        $chapter = $snapshot->chapter;
        $values = isset($payload[$chapter]) && is_array($payload[$chapter])
            ? $payload[$chapter]
            : array_diff_key($payload, array_flip(['includes_reclamation', 'land_relation', 'has_existing_permits']));
        $attachments = $this->attachmentData($snapshot->attachments);
        $inline = [];
        foreach ($attachments as $attachment) {
            if ($attachment['placement'] !== 'inline') {
                continue;
            }
            $field = $this->anchors->resolve($chapter, (string) $attachment['anchor_key'], $payload);
            if ($field !== null) {
                $attachment['render_anchor_key'] = $field;
                $inline[$field][] = $attachment;
            }
        }

        $number = 1;
        $orderedInline = [];
        foreach (array_keys($values) as $field) {
            if (! isset($inline[$field])) {
                continue;
            }

            $orderedInline[$field] = [];
            foreach ($inline[$field] as $attachment) {
                $attachment['number'] = $this->attachmentNumber($chapter, $number++);
                $orderedInline[$field][] = $attachment;
            }
        }
        foreach ($inline as $field => $items) {
            if (array_key_exists($field, $orderedInline)) {
                continue;
            }

            $orderedInline[$field] = [];
            foreach ($items as $attachment) {
                $attachment['number'] = $this->attachmentNumber($chapter, $number++);
                $orderedInline[$field][] = $attachment;
            }
        }
        $inline = $orderedInline;

        $appendixAttachments = collect($attachments)
            ->filter(fn (array $item): bool => $item['placement'] !== 'inline')
            ->values()
            ->map(function (array $attachment) use ($chapter, &$number): array {
                $attachment['number'] = $this->attachmentNumber($chapter, $number++);

                return $attachment;
            })
            ->all();

        $orderedValues = [];
        foreach (ProposalDocumentSectionCatalog::orderedFields($chapter, array_keys($values)) as $field) {
            $orderedValues[$field] = $values[$field];
        }
        $values = $orderedValues;
        $fieldLabels = [];
        foreach (array_keys($values) as $field) {
            $fieldLabels[$field] = ProposalFieldCatalog::label($field);
        }

        return [
            'chapter' => $chapter,
            'chapterTitle' => $this->chapterTitle($chapter),
            'ticket' => $snapshot->payload['ticket_number'] ?? '',
            'year' => $snapshot->payload['proposal_year'] ?? now()->year,
            'values' => $values,
            'fieldLabels' => $fieldLabels,
            'sectionLabels' => array_filter(
                array_combine(
                    array_keys($values),
                    array_map(
                        fn (string $field): ?string => ProposalDocumentSectionCatalog::heading($chapter, $field),
                        array_keys($values),
                    ),
                ),
                fn (?string $label): bool => $label !== null,
            ),
            'inlineAttachments' => $inline,
            'appendixAttachments' => $appendixAttachments,
        ];
    }

    /** @param list<array<string, mixed>> $manifest */
    private function attachmentData(array $manifest): array
    {
        return array_map(function (array $item): array {
            if (($item['storage_disk'] ?? null) !== $this->disk()) {
                throw new PdfAttachmentRenderFailed;
            }

            $storage = Storage::disk($item['storage_disk']);
            if (! $storage->exists($item['storage_path'])) {
                throw new PdfAttachmentRenderFailed;
            }

            $binary = $storage->get($item['storage_path']);
            $isImage = str_starts_with($item['mime_type'], 'image/');
            $mime = $item['mime_type'];

            if ($mime === 'image/webp') {
                $binary = $this->normalizeWebp($binary);
                $mime = 'image/png';
            }

            $pages = $isImage ? [] : $this->extractPdfPages($binary);
            $renderedPages = $isImage ? [] : $this->renderPdfPages($binary);

            if (! $isImage && $renderedPages === [] && $pages === []) {
                throw new PdfAttachmentRenderFailed;
            }

            $textPages = $pages !== [] ? $pages : ['PDF lampiran tidak memuat teks yang dapat diekstrak.'];

            return $item + [
                'is_image' => $isImage,
                'mime' => $mime,
                'binary' => $binary,
                'text' => $isImage ? null : implode("\n\n", $textPages),
                'pages' => $textPages,
                'rendered_pages' => $renderedPages,
                'data_uri' => $isImage ? 'data:'.$mime.';base64,'.base64_encode($binary) : null,
                'dimensions' => $isImage ? ($this->dimensions($binary) ?? [600, 400]) : null,
            ];
        }, $manifest);
    }

    private function attachmentNumber(string $chapter, int $number): string
    {
        return 'Lampiran '.str_replace('bag-', 'Bab ', $chapter).'-'.$number;
    }

    private function chapterTitle(string $chapter): string
    {
        return self::CHAPTER_TITLES[$chapter] ?? strtoupper(str_replace('bag-', 'BAB ', $chapter));
    }

    private function normalizeWebp(string $binary): string
    {
        $temporary = $this->writeTemporaryBinary($binary);
        $image = @imagecreatefromwebp($temporary);

        if ($image === false) {
            @unlink($temporary);
            throw new \RuntimeException('WebP normalization failed.');
        }

        ob_start();
        imagepng($image);
        $normalized = ob_get_clean();
        imagedestroy($image);
        @unlink($temporary);

        if (! is_string($normalized)) {
            throw new \RuntimeException('WebP normalization failed.');
        }

        return $normalized;
    }

    private function writeTemporaryBinary(string $binary): string
    {
        $path = tempnam(sys_get_temp_dir(), 'kkprl-media-');
        if ($path === false || file_put_contents($path, $binary) === false) {
            throw new \RuntimeException('Unable to prepare attachment render.');
        }

        return $path;
    }

    /** @return list<string> */
    private function extractPdfPages(string $binary): array
    {
        try {
            $pages = array_map(
                fn ($page): string => trim($page->getText()),
                (new Parser)->parseContent($binary)->getPages(),
            );
            $pages = array_values(array_filter($pages, fn (string $page): bool => $page !== ''));

            return $pages;
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return list<array{binary: string, mime: string, dimensions: array{0: int, 1: int}, data_uri: string}> */
    private function renderPdfPages(string $binary): array
    {
        if (class_exists(\Imagick::class)) {
            try {
                $pdf = new \Imagick;
                $pdf->setResolution(144, 144);
                $pdf->readImageBlob($binary);
                $pages = [];

                foreach ($pdf as $page) {
                    $page->setImageFormat('png');
                    $pageBinary = $page->getImageBlob();
                    $dimensions = $this->dimensions($pageBinary) ?? [600, 800];
                    $pages[] = [
                        'binary' => $pageBinary,
                        'mime' => 'image/png',
                        'dimensions' => $dimensions,
                        'data_uri' => 'data:image/png;base64,'.base64_encode($pageBinary),
                    ];
                }

                $pdf->clear();
                $pdf->destroy();

                if ($pages !== []) {
                    return $pages;
                }
            } catch (\Throwable) {
                // Fall through to the configured converter.
            }
        }

        return $this->renderPdfPagesWithBinary($binary);
    }

    /** @return list<array{binary: string, mime: string, dimensions: array{0: int, 1: int}, data_uri: string}> */
    private function renderPdfPagesWithBinary(string $binary): array
    {
        $renderer = config('kkprl.pdf_renderer.binary');
        if (! is_string($renderer) || trim($renderer) === '' || ! function_exists('proc_open')) {
            return [];
        }

        $inputPath = $this->writeTemporaryBinary($binary);
        $outputPrefix = tempnam(sys_get_temp_dir(), 'kkprl-pdf-pages-');

        if ($outputPrefix === false) {
            @unlink($inputPath);

            return [];
        }

        @unlink($outputPrefix);
        $prependArguments = config('kkprl.pdf_renderer.prepend_arguments', []);
        $prependArguments = is_array($prependArguments)
            ? array_values(array_map('strval', $prependArguments))
            : [];
        $format = strtolower((string) config('kkprl.pdf_renderer.format', 'png'));
        if (! in_array($format, ['png', 'bmp'], true)) {
            @unlink($inputPath);
            @unlink($outputPrefix);

            return [];
        }
        $formatArguments = $format === 'bmp' ? [] : ['-png'];
        $rendererArguments = $format === 'bmp'
            ? [$inputPath, $outputPrefix]
            : [
                '-r',
                (string) max(72, (int) config('kkprl.pdf_renderer.resolution_dpi', 144)),
                $inputPath,
                $outputPrefix,
            ];
        $command = array_merge(
            [$renderer],
            $prependArguments,
            $formatArguments,
            $rendererArguments,
        );
        $pipes = [];
        $process = @proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (! is_resource($process)) {
            @unlink($inputPath);

            return [];
        }

        fclose($pipes[0]);
        foreach ([1, 2] as $pipe) {
            stream_set_blocking($pipes[$pipe], false);
        }

        $timedOut = false;
        $timeout = max(1, (int) config('kkprl.pdf_renderer.timeout_seconds', 60));
        $startedAt = microtime(true);
        do {
            $status = proc_get_status($process);
            foreach ([1, 2] as $pipe) {
                stream_get_contents($pipes[$pipe]);
            }

            if (! $status['running']) {
                break;
            }

            if (microtime(true) - $startedAt >= $timeout) {
                proc_terminate($process);
                $timedOut = true;
                break;
            }

            usleep(10_000);
        } while (true);

        foreach ([1, 2] as $pipe) {
            fclose($pipes[$pipe]);
        }
        $exitCode = proc_close($process);

        $paths = glob($outputPrefix.'*'.($format === 'bmp' ? '.bmp' : '.png')) ?: [];
        natsort($paths);
        $pages = [];
        foreach ($paths as $path) {
            $pageBinary = $format === 'bmp'
                ? $this->convertBmpToPng($path)
                : file_get_contents($path);
            @unlink($path);
            if (! is_string($pageBinary)) {
                continue;
            }

            $dimensions = $this->dimensions($pageBinary) ?? [600, 800];
            $pages[] = [
                'binary' => $pageBinary,
                'mime' => 'image/png',
                'dimensions' => $dimensions,
                'data_uri' => 'data:image/png;base64,'.base64_encode($pageBinary),
            ];
        }

        @unlink($inputPath);
        @unlink($outputPrefix);

        return $timedOut || $exitCode !== 0 ? [] : array_values($pages);
    }

    private function convertBmpToPng(string $path): ?string
    {
        if (! function_exists('imagecreatefrombmp')) {
            return null;
        }

        $image = @imagecreatefrombmp($path);
        if ($image === false) {
            return null;
        }

        ob_start();
        $written = imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $written && is_string($png) ? $png : null;
    }

    /** @return array{0: int, 1: int}|null */
    private function dimensions(string $binary): ?array
    {
        $dimensions = @getimagesizefromstring($binary);

        return $dimensions === false ? null : [$dimensions[0], $dimensions[1]];
    }

    /** @param array<string, mixed> $data @param array<int, array<string, string>> $relationships @param array<int, array<string, mixed>> $media */
    private function docxDocumentXml(ProposalSnapshot $snapshot, array $data, array &$relationships, array &$media): string
    {
        $body = $this->paragraph('PROPOSAL PERMOHONAN “'.$this->chapterTitle($snapshot->chapter).'”', true);
        $body .= $this->paragraph((string) ($data['values']['institution_name'] ?? 'NAMA PERUSAHAAN/PERORANGAN/INSTANSI'), true);
        $body .= $this->paragraph('PROPOSAL KKPRL — '.strtoupper(str_replace('bag-', 'BAB ', $snapshot->chapter)), true);
        $body .= $this->paragraph('PERSETUJUAN KEGIATAN KESESUAIAN PEMANFAATAN RUANG LAUT (PKKPRL)', true);
        $body .= $this->paragraph('TAHUN '.($data['year'] ?? ''), true);
        $body .= $this->paragraph('Nomor tiket: '.($data['ticket'] ?? ''));
        $body .= $this->docxPageBreak();

        $lastSection = null;
        foreach ($data['values'] as $field => $value) {
            $section = $data['sectionLabels'][$field] ?? null;
            if ($section !== null && $section !== $lastSection) {
                $body .= $this->paragraph($section, true);
                $lastSection = $section;
            }
            $body .= $this->paragraph(($data['fieldLabels'][$field] ?? ucwords(str_replace('_', ' ', (string) $field))).':', true);
            $body .= $this->isTableValue($value)
                ? ((count(array_keys($value[0] ?? [])) > 4 ? $this->docxSectionBreak(true) : '')
                    .$this->docxTable($value)
                    .(count(array_keys($value[0] ?? [])) > 4 ? $this->docxSectionBreak(false) : ''))
                : $this->paragraph(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE));

            foreach ($data['inlineAttachments'][$field] ?? [] as $attachment) {
                $body .= $this->paragraph($attachment['number'].' — '.($attachment['original_name'] ?? ''), true);
                $body .= $attachment['is_image']
                    ? $this->docxAttachment($attachment, $relationships, $media)
                    : $this->docxPdfPages($attachment, $relationships, $media);
                $body .= $this->paragraph('Keterangan: '.($attachment['caption'] ?? ''));
            }
        }

        if ($data['appendixAttachments'] !== []) {
            $body .= $this->paragraph('LAMPIRAN BAB', true);
            foreach ($data['appendixAttachments'] as $attachment) {
                $body .= $this->paragraph($attachment['number'].' — '.$attachment['original_name'], true);
                if ($attachment['is_image']) {
                    $body .= $this->docxAttachment($attachment, $relationships, $media);
                } else {
                    $body .= $this->docxPdfPages($attachment, $relationships, $media);
                }
                $body .= $this->paragraph('Keterangan: '.($attachment['caption'] ?? ''));
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<w:body>'.$body.'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="900" w:right="900" w:bottom="900" w:left="900"/></w:sectPr></w:body></w:document>';
    }

    private function isTableValue(mixed $value): bool
    {
        return is_array($value)
            && array_is_list($value)
            && is_array($value[0] ?? null)
            && ! array_is_list($value[0]);
    }

    /** @param list<array<string, mixed>> $rows */
    private function docxTable(array $rows): string
    {
        $columns = array_keys($rows[0] ?? []);
        $xml = '<w:tbl><w:tblPr><w:tblLayout w:type="autofit"/></w:tblPr>';
        $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
        foreach ($columns as $column) {
            $xml .= '<w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>'.htmlspecialchars(ucwords(str_replace('_', ' ', $column)), ENT_XML1 | ENT_QUOTES, 'UTF-8').'</w:t></w:r></w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        foreach ($rows as $row) {
            $xml .= '<w:tr>';
            foreach ($columns as $column) {
                $value = $row[$column] ?? '';
                $text = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                $xml .= '<w:tc><w:p><w:r><w:t xml:space="preserve">'.htmlspecialchars($text ?: '', ENT_XML1 | ENT_QUOTES, 'UTF-8').'</w:t></w:r></w:p></w:tc>';
            }
            $xml .= '</w:tr>';
        }

        return $xml.'</w:tbl>';
    }

    private function docxSectionBreak(bool $landscape): string
    {
        $size = $landscape
            ? '<w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>'
            : '<w:pgSz w:w="11906" w:h="16838"/>';

        return '<w:p><w:pPr><w:sectPr>'.$size.'<w:pgMar w:top="900" w:right="900" w:bottom="900" w:left="900"/></w:sectPr></w:pPr></w:p>';
    }

    /** @param array<string, mixed> $attachment @param array<int, array<string, string>> $relationships @param array<int, array<string, mixed>> $media */
    private function docxAttachment(array $attachment, array &$relationships, array &$media): string
    {
        if (! $attachment['is_image']) {
            return '';
        }

        $index = count($media) + 1;
        $rid = 'rId'.(count($relationships) + 1);
        $extension = $attachment['mime'] === 'image/png' ? 'png' : 'jpg';
        $path = 'word/media/image'.$index.'.'.$extension;
        $relationships[] = ['id' => $rid, 'target' => 'media/image'.$index.'.'.$extension, 'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image'];
        $media[] = ['path' => $path, 'binary' => $attachment['binary'], 'content_type' => $attachment['mime']];
        [$sourceWidth, $sourceHeight] = $attachment['dimensions'] ?? [600, 400];
        $scale = min(1, 600 / max(1, $sourceWidth), 720 / max(1, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));

        return '<w:p><w:r><w:drawing><wp:inline><wp:extent cx="'.($width * 9525).'" cy="'.($height * 9525).'"/><wp:docPr id="'.$index.'" name="Attachment '.$index.'"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="'.$index.'" name="image'.$index.'"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="'.$rid.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.($width * 9525).'" cy="'.($height * 9525).'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
    }

    /** @param array<string, mixed> $attachment @param array<int, array<string, string>> $relationships @param array<int, array<string, mixed>> $media */
    private function docxPdfPages(array $attachment, array &$relationships, array &$media): string
    {
        $xml = '';
        if (($attachment['rendered_pages'] ?? []) !== []) {
            foreach ($attachment['rendered_pages'] as $index => $page) {
                $xml .= $this->paragraph('Halaman PDF '.($index + 1), true);
                $xml .= $this->docxAttachment([
                    'is_image' => true,
                    'mime' => $page['mime'],
                    'binary' => $page['binary'],
                    'dimensions' => $page['dimensions'],
                ], $relationships, $media);
                if ($index < count($attachment['rendered_pages']) - 1) {
                    $xml .= $this->docxPageBreak();
                }
            }

            return $xml;
        }

        foreach (($attachment['pages'] ?? [(string) ($attachment['text'] ?? '')]) as $index => $page) {
            $xml .= $this->paragraph('Halaman PDF '.($index + 1), true);
            $xml .= $this->paragraph((string) $page);
            if ($index < count($attachment['pages'] ?? []) - 1) {
                $xml .= $this->docxPageBreak();
            }
        }

        return $xml;
    }

    private function docxPageBreak(): string
    {
        return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }

    private function paragraph(string $text, bool $bold = false): string
    {
        $run = '<w:r>'.($bold ? '<w:rPr><w:b/></w:rPr>' : '').'<w:t xml:space="preserve">'.htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</w:t></w:r>';

        return '<w:p>'.$run.'</w:p>';
    }

    /** @param array<int, array<string, string>> $relationships */
    private function docxRelationshipsXml(array $relationships): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($relationships as $relationship) {
            $xml .= '<Relationship Id="'.$relationship['id'].'" Type="'.$relationship['type'].'" Target="'.$relationship['target'].'"/>';
        }

        return $xml.'</Relationships>';
    }

    /** @param array<int, array<string, mixed>> $media */
    private function docxContentTypesXml(array $media): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>';
        $extensions = [];
        foreach ($media as $item) {
            $extension = pathinfo($item['path'], PATHINFO_EXTENSION);
            if (isset($extensions[$extension])) {
                continue;
            }
            $extensions[$extension] = true;
            $xml .= '<Default Extension="'.$extension.'" ContentType="'.$item['content_type'].'"/>';
        }

        return $xml.'</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>';
    }

    private function docxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/></w:rPr></w:rPrDefault></w:docDefaults></w:styles>';
    }
}
