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
        $phoneHash = $this->hashes->phone($this->phones->normalize($credential));

        if ($phoneHash === null) {
            return null;
        }

        $proposals = KkprlProposal::query()
            ->where('ticket_number', strtoupper(trim($ticketNumber)))
            ->where('phone_hash', $phoneHash)
            ->orderByDesc('revision_number')
            ->get();

        return $proposals->first(
            fn (KkprlProposal $proposal): bool => $proposal->status === 'draft' && $proposal->revision_number > 0,
        ) ?? $proposals->first();
    }
}
