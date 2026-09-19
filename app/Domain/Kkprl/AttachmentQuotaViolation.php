<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class AttachmentQuotaViolation extends RuntimeException
{
    public function __construct(public readonly string $kind, string $message)
    {
        parent::__construct($kind.': '.$message);
    }
}
