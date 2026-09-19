<?php

namespace App\Livewire;

class KkprlProposalResume extends KkprlProposalAccess
{
    public function mount(): void
    {
        $this->mode = 'resume';
    }
}
