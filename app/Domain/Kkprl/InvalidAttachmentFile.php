<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class InvalidAttachmentFile extends RuntimeException
{
    public function __construct(string $reason = 'File attachment is invalid.')
    {
        parent::__construct($reason);
    }
}
