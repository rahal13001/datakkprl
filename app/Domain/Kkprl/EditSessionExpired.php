<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class EditSessionExpired extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Edit session has expired or ended.');
    }
}
