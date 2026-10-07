<?php

namespace App\Services;

use App\Contracts\KkprlProposalAccessVerifier;
use App\Domain\Kkprl\KkprlPhoneNormalizer;
use App\Models\KkprlProposal;

final class KkprlTicketPhoneAccessVerifier implements KkprlProposalAccessVerifier
{
    public function __construct(
        private readonly KkprlPhoneNormalizer $phones,
        private readonly DataHashService $hashes,
    ) {}

    public function verify(string $ticketNumber, string $credential): ?KkprlProposal
    {
        // Retained for legacy integrations: credentials never grant account access.
        return null;
    }
}
