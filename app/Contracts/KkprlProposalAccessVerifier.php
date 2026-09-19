<?php

namespace App\Contracts;

use App\Models\KkprlProposal;

interface KkprlProposalAccessVerifier
{
    public function verify(string $ticketNumber, string $credential): ?KkprlProposal;
}
