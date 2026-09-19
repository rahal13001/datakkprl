<?php

namespace App\Domain\Kkprl;

final readonly class ProposalSnapshot
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array<string, mixed>>  $attachments
     */
    public function __construct(
        public string $chapter,
        public string $templateVersion,
        public array $payload,
        public array $attachments,
        public string $snapshotHash,
        public string $attachmentManifestHash,
    ) {}
}
