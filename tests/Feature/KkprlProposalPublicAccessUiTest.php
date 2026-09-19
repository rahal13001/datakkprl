<?php

namespace Tests\Feature;

use App\Livewire\KkprlProposalResume;
use App\Livewire\KkprlProposalStart;
use App\Services\KkprlProposalAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KkprlProposalPublicAccessUiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_access_routes_render_with_the_application_layout(): void
    {
        $this->get(route('kkprl.proposal.start'))
            ->assertOk()
            ->assertSee('Buat Proposal Baru')
            ->assertSee('aria-label="Buka bantuan chat"', false)
            ->assertSee('name="description"', false);

        $this->get(route('kkprl.proposal.resume'))
            ->assertOk()
            ->assertSee('Lanjutkan Proposal');

        app(KkprlProposalAccessService::class)->startDraft([
            'phone' => '0812-3456-7890',
        ]);

        $this->get(route('kkprl.proposal.wizard'))
            ->assertOk()
            ->assertSee('Pengisian Proposal')
            ->assertSee('data-kkprl-wizard', false)
            ->assertSee('pb-32', false)
            ->assertSee('for="kkprl-pending-files"', false)
            ->assertSee('Pilih file lampiran')
            ->assertSee('formnovalidate', false);
    }

    #[Test]
    public function start_page_creates_draft_and_shows_ticket(): void
    {
        Livewire::test(KkprlProposalStart::class)
            ->assertSee('Membuat draft...')
            ->set('phone', '0812-3456-7890')
            ->call('submit')
            ->assertSet('createdTicket', fn (?string $ticket): bool => filled($ticket))
            ->assertSee('Simpan nomor tiket');
    }

    #[Test]
    public function resume_page_grants_access_with_valid_credentials(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        app(KkprlProposalAccessService::class)->forget();

        Livewire::test(KkprlProposalResume::class)
            ->assertSee('Memverifikasi...')
            ->set('ticketNumber', $proposal->ticket_number)
            ->set('phone', '+62 812 3456 7890')
            ->call('submit')
            ->assertSet('accessGranted', true)
            ->assertHasNoErrors();
    }

    #[Test]
    public function resume_page_does_not_reveal_which_credential_is_wrong(): void
    {
        Livewire::test(KkprlProposalResume::class)
            ->set('ticketNumber', 'KKPRL-UNKNOWN')
            ->set('phone', '0812-0000-0000')
            ->call('submit')
            ->assertHasErrors(['credentials']);
    }
}
