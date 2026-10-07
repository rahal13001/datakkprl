<?php

namespace App\Livewire;

class KkprlApplicantRegister extends KkprlApplicantLogin
{
    public function mount(): void
    {
        $this->registering = true;
    }
}
