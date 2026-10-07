<?php

namespace App\Services;

use App\Domain\Kkprl\AttachmentQuota;
use App\Domain\Kkprl\InvalidAttachmentFile;
use App\Domain\Kkprl\ProposalChapterRules;
use App\Domain\Kkprl\ProposalInlineAnchorResolver;
use App\Domain\Kkprl\ProposalLocked;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class KkprlProposalAttachmentService
{
    /** @var array<string, string> */
    private const MIME_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly ProposalChapterRules $chapterRules,
        private readonly AttachmentQuota $quota,
        private readonly ProposalInlineAnchorResolver $anchors,
    ) {}

    private function disk(): string
    {
        return (string) config('kkprl.storage_disk', 'kkprl_private');
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function store(
        KkprlProposal $proposal,
        string $chapter,
        UploadedFile $file,
        array $metadata = [],
    ): KkprlProposalAttachment {
        return $this->storeMany($proposal, $chapter, [$file], $metadata)[0];
    }

    /**
     * @param  list<UploadedFile>  $files
     * @param  array<string, mixed>  $metadata
     * @return list<KkprlProposalAttachment>
     */
    public function storeMany(
        KkprlProposal $proposal,
        string $chapter,
        array $files,
        array $metadata = [],
    ): array {
        app(KkprlProposalAccessService::class)->assertCanBuild($proposal);
        if ($proposal->isLocked()) {
            throw new ProposalLocked;
        }

        if ($files === []) {
            throw new InvalidAttachmentFile('No attachment was provided.');
        }

        if (! $this->chapterRules->isRelevant($chapter, $proposal->payload ?? [])) {
            throw new InvalidAttachmentFile('Attachment chapter is not active.');
        }

        $placement = $metadata['placement'] ?? 'appendix';
        $this->assertPlacement($placement, $metadata, $proposal->payload ?? [], $chapter);
        $inspected = array_map(fn (UploadedFile $file): array => [
            'file' => $file,
            'inspection' => $this->inspect($file, $chapter, $metadata),
        ], $files);
        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $proposal, $chapter, $metadata, $placement, $inspected, &$storedPaths
            ): array {
                $lockedProposal = KkprlProposal::query()->lockForUpdate()->findOrFail($proposal->id);
                app(KkprlProposalAccessService::class)->assertCanBuild($lockedProposal, lock: true);
                if ($lockedProposal->isLocked()) {
                    throw new ProposalLocked;
                }

                if (! $this->chapterRules->isRelevant($chapter, $lockedProposal->payload ?? [])) {
                    throw new InvalidAttachmentFile('Attachment chapter is not active.');
                }

                $this->assertPlacement($placement, $metadata, $lockedProposal->payload ?? [], $chapter);

                $query = $lockedProposal->attachments()->where('chapter', $chapter)->lockForUpdate();
                $totalSize = (int) $query->sum('size');
                foreach ($inspected as $item) {
                    $totalSize += $item['inspection'][2];
                }
                $this->quota->assertWithin(
                    $query->count() + count($inspected),
                    $totalSize,
                );

                $directory = "kkprl/proposals/{$lockedProposal->root_proposal_id}/{$lockedProposal->id}/{$chapter}";
                $attachments = [];
                foreach ($inspected as $item) {
                    /** @var UploadedFile $file */
                    $file = $item['file'];
                    [$mime, $extension, $size, $checksum] = $item['inspection'];
                    $filename = Str::uuid().'.'.$extension;
                    $path = Storage::disk($this->disk())->putFileAs($directory, $file, $filename);

                    if ($path === false) {
                        throw new InvalidAttachmentFile('Attachment storage failed.');
                    }
                    $storedPaths[] = $path;

                    $attachments[] = $lockedProposal->attachments()->create([
                        'chapter' => $chapter,
                        'section' => $metadata['section'] ?? null,
                        'field' => $metadata['field'] ?? ($placement === 'inline' ? $metadata['anchor_key'] : null),
                        'attachment_role' => $metadata['attachment_role'] ?? 'supporting',
                        'placement' => $placement,
                        'anchor_key' => $metadata['anchor_key'] ?? null,
                        'display_order' => (int) ($metadata['display_order'] ?? 0),
                        'caption' => $metadata['caption'] ?? null,
                        'source_metadata' => $metadata['source_metadata'] ?? null,
                        'original_name' => $this->displayName($file->getClientOriginalName()),
                        'storage_disk' => $this->disk(),
                        'storage_path' => $path,
                        'mime_type' => $mime,
                        'size' => $size,
                        'checksum' => $checksum,
                        'uploaded_at' => now(),
                    ]);
                }

                return $attachments;
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk($this->disk())->delete($path);
            }

            throw $exception;
        }
    }

    public function delete(KkprlProposal $proposal, KkprlProposalAttachment $attachment): void
    {
        app(KkprlProposalAccessService::class)->assertCanBuild($proposal);
        if ($proposal->isLocked()) {
            throw new ProposalLocked;
        }

        if ((int) $attachment->proposal_id !== (int) $proposal->id) {
            throw new InvalidAttachmentFile('Attachment does not belong to proposal.');
        }

        DB::transaction(function () use ($proposal, $attachment): void {
            $lockedProposal = KkprlProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            app(KkprlProposalAccessService::class)->assertCanBuild($lockedProposal, lock: true);
            if ($lockedProposal->isLocked()) {
                throw new ProposalLocked;
            }

            $lockedAttachment = $lockedProposal->attachments()->lockForUpdate()->findOrFail($attachment->id);
            Storage::disk($lockedAttachment->storage_disk)->delete($lockedAttachment->storage_path);
            $lockedAttachment->delete();
        });
    }

    /** @param array<string, mixed> $metadata */
    public function replace(
        KkprlProposal $proposal,
        KkprlProposalAttachment $attachment,
        UploadedFile $file,
        array $metadata = [],
    ): KkprlProposalAttachment {
        app(KkprlProposalAccessService::class)->assertCanBuild($proposal);
        if ($proposal->isLocked()) {
            throw new ProposalLocked;
        }

        if ((int) $attachment->proposal_id !== (int) $proposal->id) {
            throw new InvalidAttachmentFile('Attachment does not belong to proposal.');
        }

        if (! $this->chapterRules->isRelevant($attachment->chapter, $proposal->payload ?? [])) {
            throw new InvalidAttachmentFile('Attachment chapter is not active.');
        }

        [$mime, $extension, $size, $checksum] = $this->inspect($file);
        $placement = $metadata['placement'] ?? $attachment->placement;
        $this->assertPlacement($placement, $metadata + ['anchor_key' => $attachment->anchor_key], $proposal->payload ?? [], $attachment->chapter);

        $storedPath = null;

        try {
            [$updated, $oldDisk, $oldPath] = DB::transaction(function () use (
                $proposal, $attachment, $file, $metadata, $mime, $extension, $size, $checksum, $placement, &$storedPath
            ): array {
                $lockedProposal = KkprlProposal::query()->lockForUpdate()->findOrFail($proposal->id);
                app(KkprlProposalAccessService::class)->assertCanBuild($lockedProposal, lock: true);
                if ($lockedProposal->isLocked()) {
                    throw new ProposalLocked;
                }

                $lockedAttachment = $lockedProposal->attachments()->lockForUpdate()->findOrFail($attachment->id);
                if (! $this->chapterRules->isRelevant($lockedAttachment->chapter, $lockedProposal->payload ?? [])) {
                    throw new InvalidAttachmentFile('Attachment chapter is not active.');
                }

                $this->assertPlacement(
                    $placement,
                    $metadata + ['anchor_key' => $lockedAttachment->anchor_key],
                    $lockedProposal->payload ?? [],
                    $lockedAttachment->chapter,
                );
                $oldDisk = $lockedAttachment->storage_disk;
                $oldPath = $lockedAttachment->storage_path;
                $query = $lockedProposal->attachments()->where('chapter', $lockedAttachment->chapter)->where('kkprl_proposal_attachments.id', '!=', $lockedAttachment->id)->lockForUpdate();
                $this->quota->assertWithin($query->count() + 1, (int) $query->sum('size') + $size);
                $directory = "kkprl/proposals/{$lockedProposal->root_proposal_id}/{$lockedProposal->id}/{$lockedAttachment->chapter}";
                $filename = Str::uuid().'.'.$extension;
                $path = Storage::disk($this->disk())->putFileAs($directory, $file, $filename);

                if ($path === false) {
                    throw new InvalidAttachmentFile('Attachment storage failed.');
                }

                $storedPath = $path;
                $lockedAttachment->update([
                    'section' => $metadata['section'] ?? $lockedAttachment->section,
                    'field' => $metadata['field'] ?? ($placement === 'inline' ? ($metadata['anchor_key'] ?? $lockedAttachment->anchor_key) : $lockedAttachment->field),
                    'attachment_role' => $metadata['attachment_role'] ?? ($lockedAttachment->source_metadata ? 'supporting' : $lockedAttachment->attachment_role),
                    'placement' => $placement,
                    'anchor_key' => $metadata['anchor_key'] ?? ($placement === 'inline' ? $lockedAttachment->anchor_key : null),
                    'caption' => $metadata['caption'] ?? ($lockedAttachment->source_metadata ? null : $lockedAttachment->caption),
                    'source_metadata' => $metadata['source_metadata'] ?? null,
                    'original_name' => $this->displayName($file->getClientOriginalName()),
                    'storage_disk' => $this->disk(),
                    'storage_path' => $path,
                    'mime_type' => $mime,
                    'size' => $size,
                    'checksum' => $checksum,
                    'uploaded_at' => now(),
                ]);

                return [$lockedAttachment->fresh(), $oldDisk, $oldPath];
            });
        } catch (\Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk($this->disk())->delete($storedPath);
            }

            throw $exception;
        }

        Storage::disk($oldDisk)->delete($oldPath);

        return $updated;
    }

    /** @return array{0: string, 1: string, 2: int, 3: string} */
    private function inspect(UploadedFile $file, string $chapter = '', array $metadata = []): array
    {
        $realPath = $file->getRealPath();

        if (! is_string($realPath) || ! is_file($realPath)) {
            Log::error('Upload failed: not a file. Path: '.json_encode($realPath));
            throw new InvalidAttachmentFile('Path bukan file valid.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($realPath);
        $signature = file_get_contents($realPath, false, null, 0, 16);
        $size = filesize($realPath);

        if (($metadata['attachment_role'] ?? null) === 'ai_tide_data') {
            if ($chapter !== 'bag-3' || ($metadata['field'] ?? null) !== 'tide'
                || ($metadata['placement'] ?? null) !== 'inline' || ($metadata['anchor_key'] ?? null) !== 'tide') {
                throw new InvalidAttachmentFile('Data pasut hanya tersedia pada bagian pasang surut Bab 3.');
            }
            $extension = strtolower($file->getClientOriginalExtension());
            app(KkprlTideDataReader::class)->read($realPath, $extension);

            return [match ($extension) {
                'csv' => 'text/csv',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            }, $extension, (int) $size, hash_file('sha256', $realPath) ?: ''];
        }

        if (! is_string($mime) || ! isset(self::MIME_EXTENSIONS[$mime]) || ! is_string($signature) || $size === false) {
            Log::error('Upload failed: mime/signature/size issue.', [
                'mime' => $mime,
                'is_mime_supported' => is_string($mime) ? isset(self::MIME_EXTENSIONS[$mime]) : false,
                'has_signature' => is_string($signature),
                'size' => $size,
            ]);
            throw new InvalidAttachmentFile("Tipe file tidak didukung: {$mime}");
        }

        $validSignature = match ($mime) {
            'application/pdf' => Str::startsWith($signature, '%PDF-'),
            'image/jpeg' => str_starts_with($signature, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($signature, "\x89PNG\x0D\x0A\x1A\x0A"),
            'image/webp' => str_starts_with($signature, 'RIFF')
                && substr($signature, 8, 4) === 'WEBP',
            default => false,
        };

        if (! $validSignature) {
            Log::error("Upload failed: Invalid signature for $mime", [
                'hex_signature' => bin2hex($signature),
            ]);
            throw new InvalidAttachmentFile('Format internal file tidak cocok dengan ekstensinya (File Corrupt/Palsu).');
        }

        return [$mime, self::MIME_EXTENSIONS[$mime], $size, hash_file('sha256', $realPath) ?: ''];
    }

    /** @param array<string, mixed> $metadata */
    /** @param array<string, mixed> $payload */
    private function assertPlacement(string $placement, array $metadata, array $payload, string $chapter): void
    {
        if (! in_array($placement, ['inline', 'appendix'], true)) {
            throw new InvalidAttachmentFile('Attachment placement is invalid.');
        }

        if ($placement === 'inline' && blank($metadata['anchor_key'] ?? null)) {
            throw new InvalidAttachmentFile('Inline attachment requires an anchor.');
        }

        if ($placement === 'inline'
            && $this->anchors->resolve($chapter, (string) $metadata['anchor_key'], $payload) === null) {
            throw new InvalidAttachmentFile('Inline attachment anchor was not found.');
        }

    }

    private function displayName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\pL\pN._-]+/u', '_', $name) ?: 'attachment';

        return mb_substr(trim($name, '._-') ?: 'attachment', 0, 255);
    }
}
