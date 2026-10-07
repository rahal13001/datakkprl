<?php

namespace App\Services;

use App\Domain\Kkprl\ProposalChapterRules;
use App\Domain\Kkprl\ProposalFieldCatalog;
use App\Domain\Kkprl\ProposalInlineAnchorResolver;
use App\Domain\Kkprl\ProposalProgress;
use App\Domain\Kkprl\ProposalSnapshot;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalAttachment;
use Illuminate\Validation\ValidationException;

final class KkprlProposalSnapshotService
{
    public function __construct(
        private readonly ProposalChapterRules $chapterRules,
        private readonly ProposalProgress $progress,
        private readonly ProposalInlineAnchorResolver $anchors,
    ) {}

    public function capture(KkprlProposal $proposal, string $chapter): ProposalSnapshot
    {
        $payload = $proposal->payload ?? [];

        if (! $this->chapterRules->isRelevant($chapter, $payload)) {
            throw ValidationException::withMessages([
                'chapter' => 'Bab tidak aktif untuk proposal ini.',
            ]);
        }

        $state = $this->progress->evaluate($payload)->chapters[$chapter] ?? null;

        if ($state === null || $state['status'] !== 'complete') {
            throw ValidationException::withMessages([
                'chapter' => 'Bab belum lengkap dan belum dapat diekspor.',
            ]);
        }

        $attachments = $proposal->attachments()
            ->where('chapter', $chapter)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->filter(fn ($attachment): bool => $chapter !== 'bag-5' || $this->activeChapterFiveAttachment($attachment, $payload))
            ->map(fn ($attachment): array => [
                'id' => $attachment->id,
                'chapter' => $attachment->chapter,
                'section' => $attachment->section,
                'field' => $attachment->field,
                'attachment_role' => $attachment->attachment_role,
                'placement' => $attachment->placement,
                'anchor_key' => $attachment->anchor_key,
                'display_order' => $attachment->display_order,
                'caption' => $attachment->caption,
                'original_name' => $attachment->original_name,
                'storage_disk' => $attachment->storage_disk,
                'storage_path' => $attachment->storage_path,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'checksum' => $attachment->checksum,
                ...($attachment->source_metadata ? ['source_metadata' => $attachment->source_metadata] : []),
            ])
            ->values()
            ->all();

        foreach ($attachments as $attachment) {
            if ($attachment['storage_disk'] !== config('kkprl.storage_disk', 'kkprl_private')) {
                throw ValidationException::withMessages([
                    'attachment' => 'Lampiran tidak dapat diproses dari storage yang tidak privat.',
                ]);
            }

            if ($attachment['placement'] === 'inline'
                && $this->anchors->resolve($chapter, (string) $attachment['anchor_key'], $payload) === null) {
                throw ValidationException::withMessages([
                    'attachment_anchor' => 'Anchor inline tidak ditemukan pada teks/field bab yang dipilih.',
                ]);
            }
        }

        $manifestHash = $this->hash($attachments);
        $snapshotPayload = [
            'proposal_id' => $proposal->id,
            'ticket_number' => $proposal->ticket_number,
            'proposal_year' => $proposal->created_at?->year ?? now()->year,
            'revision_label' => $proposal->revision_label,
            'chapter' => $chapter,
            'payload' => $this->payloadForChapter($payload, $chapter),
            'template_version' => $proposal->template_version,
        ];

        return new ProposalSnapshot(
            $chapter,
            $proposal->template_version,
            $snapshotPayload,
            $attachments,
            $this->hash($snapshotPayload),
            $manifestHash,
        );
    }

    private function activeChapterFiveAttachment(KkprlProposalAttachment $attachment, array $payload): bool
    {
        $active = ProposalFieldCatalog::fieldsForPayload('bag-5', $payload);
        $all = array_merge(ProposalFieldCatalog::fields('bag-5-land'), ProposalFieldCatalog::fields('bag-5-permits'));
        if (in_array($attachment->field, $all, true) && ! in_array($attachment->field, $active, true)) {
            return false;
        }
        if ($attachment->placement !== 'inline') {
            return true;
        }
        if ($this->anchors->resolve('bag-5', (string) $attachment->anchor_key, $payload) !== null) {
            return true;
        }
        // Resolve known inactive anchors using all sections, while still rejecting unknown anchors below.
        $allSections = array_replace($payload, ['land_relation' => 'adjacent', 'has_existing_permits' => true]);
        $field = $this->anchors->resolve('bag-5', (string) $attachment->anchor_key, $allSections);

        return $field === null || in_array($field, $active, true);
    }

    /** @return array<string, mixed> */
    private function payloadForChapter(array $payload, string $chapter): array
    {
        $conditions = array_intersect_key($payload, array_flip([
            'includes_reclamation', 'land_relation', 'has_existing_permits',
        ]));

        if (isset($payload[$chapter]) && is_array($payload[$chapter])) {
            $fields = ProposalFieldCatalog::fieldsForPayload($chapter, $payload);

            return $conditions + [$chapter => array_intersect_key($payload[$chapter], array_flip($fields))];
        }

        $fields = ProposalFieldCatalog::fieldsForPayload($chapter, $payload);

        return $conditions + [$chapter => array_intersect_key($payload, array_flip($fields))];
    }

    /** @param mixed $value */
    private function hash($value): string
    {
        return hash('sha256', json_encode($this->canonicalize($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
