<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class InvalidProposalCredentials extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Credential verification failed.');
    }
}
