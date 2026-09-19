<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class ProposalAccessRateLimited extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Too many resume attempts.');
    }
}
