<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class EditApprovalUsed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Edit approval has already been used.');
    }
}
