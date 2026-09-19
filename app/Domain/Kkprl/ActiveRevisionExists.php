<?php

namespace App\Domain\Kkprl;

use RuntimeException;

final class ActiveRevisionExists extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('An active draft revision already exists.');
    }
}
