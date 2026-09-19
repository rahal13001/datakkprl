<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class ProposalAccessDenied extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Proposal access is not available.');
    }
}
