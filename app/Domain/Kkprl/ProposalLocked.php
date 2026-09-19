<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class ProposalLocked extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Proposal is locked and cannot be changed.');
    }
}
