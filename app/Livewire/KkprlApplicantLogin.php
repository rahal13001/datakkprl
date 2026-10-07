<?php

namespace App\Livewire;

use App\Services\KkprlApplicantAuthService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.kkprl-applicant')]
class KkprlApplicantLogin extends Component
{
    #[Locked]
    public bool $registering = false;

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function submit(KkprlApplicantAuthService $accounts): void
    {
        try {
            $this->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'max:255']]);
            if ($this->registering) {
                $accounts->register(['email' => $this->email, 'password' => $this->password, 'password_confirmation' => $this->password_confirmation]);
            }
            $accounts->login($this->email, $this->password);
            $this->redirectRoute('kkprl.applicant.portal');
        } finally {
            $this->reset('password', 'password_confirmation');
        }
    }

    public function render()
    {
        return view('livewire.kkprl-applicant-login');
    }
}
