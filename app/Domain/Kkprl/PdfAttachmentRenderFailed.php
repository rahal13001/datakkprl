<?php

namespace App\Domain\Kkprl;

final class PdfAttachmentRenderFailed extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('PDF attachment could not be rendered.');
    }
}
