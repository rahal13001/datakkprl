<?php

namespace App\Livewire;

use App\Domain\Kkprl\InvalidProposalCredentials;
use App\Domain\Kkprl\ProposalAccessRateLimited;
use App\Services\KkprlProposalAccessService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KkprlProposalAccess extends Component
{
    public string $mode = 'start';

    public string $phone = '';

    public string $ticketNumber = '';

    public ?string $createdTicket = null;

    public bool $accessGranted = false;

    public function submit(KkprlProposalAccessService $access): void
    {
        $this->resetErrorBag();

        if ($this->mode === 'resume') {
            $this->resume($access);

            return;
        }

        try {
            $proposal = $access->startDraft([
                'phone' => $this->phone,
            ]);
            $this->createdTicket = $proposal->ticket_number;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->errors());
        }
    }

    public function render()
    {
        return view('livewire.kkprl-proposal-access');
    }

    private function resume(KkprlProposalAccessService $access): void
    {
        try {
            $access->resumeDraft($this->ticketNumber, $this->phone);
            $this->accessGranted = true;
        } catch (ProposalAccessRateLimited) {
            $this->addError('credentials', 'Terlalu banyak percobaan. Coba lagi nanti.');
        } catch (InvalidProposalCredentials) {
            $this->addError('credentials', 'Nomor tiket atau nomor HP tidak valid.');
        }
    }
}
