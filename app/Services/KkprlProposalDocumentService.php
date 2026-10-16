<?php

namespace App\Services;

use App\Domain\Kkprl\PdfAttachmentRenderFailed;
use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalDocumentPresentation;
use App\Domain\Kkprl\ProposalDocumentSectionCatalog;
use App\Domain\Kkprl\ProposalFieldCatalog;
use App\Domain\Kkprl\ProposalInlineAnchorResolver;
use App\Domain\Kkprl\ProposalLocked;
use App\Domain\Kkprl\ProposalSnapshot;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Parser;

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
        app(KkprlProposalAccessService::class)->assertCanBuild($proposal);
        $proposal = $proposal->fresh();
        if ($proposal->isLocked()) {
            throw new ProposalLocked;
        }
        $deletionMarker = (int) $proposal->reviewEvents()->where('event_type', 'document_deleted')->max('id');
        $snapshot = $this->snapshots->capture($proposal, $chapter);
        $documents = collect();
        $map = null;

        foreach (array_values(array_unique($formats)) as $format) {
            if (! in_array($format, ['docx', 'pdf'], true)) {
                continue;
            }

            $documents->push($this->generateOne($proposal, $snapshot, $format, $map, $deletionMarker));
        }

        return $documents;
    }

    private function generateOne(
        KkprlProposal $proposal,
        ProposalSnapshot $snapshot,
        string $format,
        ?array &$map,
        int $deletionMarker,
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
            && $document->deleted_at === null
            && $document->generation_status === 'generated'
            && filled($document->storage_path)
            && $document->storage_disk === $this->disk()
            && Storage::disk($this->disk())->exists($document->storage_path)) {
            return $document->fresh();
        }

        try {
            // Rendering runs outside the final persistence checkpoint.
            if ($map === null && isset($snapshot->payload['location_map'])) {
                $values = $snapshot->payload['payload']['bag-1'];
                $map = app(KkprlLocationMapRenderer::class)->render(array_replace($values, $snapshot->payload['location_map']));
            }
            $binary = $format === 'pdf'
                ? $this->renderPdf($snapshot, $map)
                : $this->renderDocx($snapshot, $map);

            return DB::transaction(function () use ($proposal, $snapshot, $format, $binary, $document, $deletionMarker): KkprlProposalDocument {
                $locked = KkprlProposal::query()->lockForUpdate()->findOrFail($proposal->id);
                app(KkprlProposalAccessService::class)->assertCanBuild($locked, lock: true);
                if ($locked->isLocked()) {
                    throw new ProposalLocked;
                }
                if ((int) $locked->reviewEvents()->where('event_type', 'document_deleted')->max('id') !== $deletionMarker) {
                    throw ValidationException::withMessages(['documents' => 'Dokumen dihapus saat proses berjalan. Buat ulang bila masih diperlukan.']);
                }
                $current = $this->snapshots->capture($locked, $snapshot->chapter);
                if (! hash_equals($snapshot->snapshotHash, $current->snapshotHash)
                    || ! hash_equals($snapshot->attachmentManifestHash, $current->attachmentManifestHash)) {
                    throw new \RuntimeException('Proposal changed during rendering.');
                }
                $existing = KkprlProposalDocument::query()->where([
                    'proposal_id' => $locked->id, 'chapter' => $snapshot->chapter, 'format' => $format,
                    'snapshot_hash' => $snapshot->snapshotHash, 'attachment_manifest_hash' => $snapshot->attachmentManifestHash,
                ])->first();
                if ($existing?->getRawOriginal('deleted_at') !== $document->getRawOriginal('deleted_at')) {
                    throw ValidationException::withMessages(['documents' => 'Dokumen dihapus saat proses berjalan. Buat ulang bila masih diperlukan.']);
                }
                if ($existing?->generation_status === 'generated'
                    && $existing->deleted_at === null
                    && $existing->storage_disk === $this->disk() && filled($existing->storage_path)
                    && Storage::disk($this->disk())->exists($existing->storage_path)) {
                    return $existing;
                }
                $path = "kkprl/proposals/{$locked->root_proposal_id}/{$locked->id}/documents/{$snapshot->chapter}-{$snapshot->snapshotHash}-{$snapshot->attachmentManifestHash}.{$format}";
                if (! Storage::disk($this->disk())->put($path, $binary)) {
                    throw new \RuntimeException('Unable to store generated document.');
                }
                $document->forceFill(['deleted_at' => null, 'deleted_by_applicant_id' => null])->fill([
                    'storage_disk' => $this->disk(),
                    'storage_path' => $path,
                    'generation_status' => 'generated',
                    'error_code' => null,
                    'generated_at' => now(),
                ])->save();

                return $document->fresh();
            });
        } catch (\Throwable $exception) {
            // A revoked identity may not even write a failed status.
            try {
                DB::transaction(function () use ($proposal, $document, $deletionMarker): void {
                    $locked = KkprlProposal::query()->lockForUpdate()->findOrFail($proposal->id);
                    app(KkprlProposalAccessService::class)->assertCanBuild($locked, lock: true);
                    $persisted = $document->exists ? $document->fresh() : null;
                    if (! $locked->isLocked()
                        && (int) $locked->reviewEvents()->where('event_type', 'document_deleted')->max('id') === $deletionMarker
                        && $persisted?->getRawOriginal('deleted_at') === $document->getRawOriginal('deleted_at')) {
                        ($persisted ?? $document)->fill(['generation_status' => 'failed', 'error_code' => 'render_failed'])->save();
                    }
                });
            } catch (ProposalAccessDenied) {
                // Preserve the original failure without writing after revocation/reset.
            }
            throw $exception;
        }
    }

    private function renderPdf(ProposalSnapshot $snapshot, ?array $map = null): string
    {
        return app(KkprlProposalPdfRenderer::class)->render($this->viewData($snapshot) + ['locationMap' => $map]);
    }

    /** @param array<string, mixed> $data */
    private function requiresLandscape(array $data): bool
    {
        foreach ($data['values'] ?? [] as $value) {
            if (! $this->isTableValue($value)) {
                continue;
            }

            if (count(ProposalDocumentPresentation::columns($value)) > 4) {
                return true;
            }
        }

        return false;
    }

    private function renderDocx(ProposalSnapshot $snapshot, ?array $map = null): string
    {
        return app(KkprlProposalDocxRenderer::class)->render($this->viewData($snapshot) + ['locationMap' => $map]);
    }

    /** @return array<string, mixed> */
    private function viewData(ProposalSnapshot $snapshot): array
    {
        $payload = $snapshot->payload['payload'] ?? [];
        $chapter = $snapshot->chapter;
        $values = isset($payload[$chapter]) && is_array($payload[$chapter])
            ? $payload[$chapter]
            : array_diff_key($payload, array_flip(['includes_reclamation', 'land_relation', 'has_existing_permits']));
        $orderedValues = [];
        foreach (ProposalDocumentSectionCatalog::orderedFields($chapter, array_keys($values)) as $field) {
            $orderedValues[$field] = $values[$field];
        }
        $values = $orderedValues;
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

        $fieldLabels = [];
        foreach (array_keys($values) as $field) {
            $fieldLabels[$field] = ProposalFieldCatalog::label($field);
        }

        $data = [
            'chapter' => $chapter,
            'chapterTitle' => $this->chapterTitle($chapter),
            'coverName' => trim(ProposalDocumentPresentation::text($snapshot->payload['cover_name'] ?? $values['institution_name'] ?? '')) ?: 'PEMOHON',
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
        $data['landscape'] = $this->requiresLandscape($data);
        $data['blocks'] = ProposalDocumentPresentation::blocks($data);

        return $data;
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

            $isTideData = ($item['attachment_role'] ?? null) === 'ai_tide_data';
            if ($isTideData && ! is_array($item['source_metadata']['tide_summary'] ?? null)) {
                throw new PdfAttachmentRenderFailed;
            }
            $pages = $isImage ? [] : ($isTideData
                ? [app(KkprlTideDataAnalyzer::class)->report($item['source_metadata']['tide_summary'])]
                : $this->extractPdfPages($binary));
            $renderedPages = $isImage || $isTideData ? [] : $this->renderPdfPages($binary);

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

    private function isTableValue(mixed $value): bool
    {
        return ProposalDocumentPresentation::isTable($value);
    }
}
