<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class EditScopeDenied extends RuntimeException
{
    public function __construct(string $field)
    {
        parent::__construct('Edit scope does not include: '.$field);
    }
}
