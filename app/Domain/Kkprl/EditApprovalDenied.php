<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class EditApprovalDenied extends RuntimeException
{
    public function __construct(string $message = 'Edit approval denied.')
    {
        parent::__construct($message);
    }
}
