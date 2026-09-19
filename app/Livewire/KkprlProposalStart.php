<?php

namespace App\Livewire;

class KkprlProposalStart extends KkprlProposalAccess
{
    public function mount(): void
    {
        $this->mode = 'start';
    }
}
