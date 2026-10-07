<?php

namespace Tests\Feature;

use App\Livewire\KkprlApplicantRegister;
use App\Livewire\KkprlProposalResume;
use App\Livewire\KkprlProposalStart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KkprlProposalPublicAccessUiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function account_routes_render_email_password_without_ticket_phone_or_chat(): void
    {
        $this->get(route('kkprl.applicant.register'))->assertOk()
            ->assertSee('Daftar akun pemohon')->assertSee('Email')->assertSee('Konfirmasi password')
            ->assertDontSee('Nomor HP')->assertDontSee('Nomor Tiket')
            ->assertDontSee('aria-label="Buka bantuan chat"', false);
        $this->get(route('kkprl.applicant.login'))->assertOk()->assertSee('Hubungi petugas');
        $this->get(route('kkprl.proposal.start'))->assertRedirect(route('kkprl.applicant.login'));
        $this->get(route('kkprl.proposal.resume'))->assertRedirect(route('kkprl.applicant.login'));
    }

    #[Test]
    public function registration_logs_in_pending_account_without_draft_or_ai(): void
    {
        Http::preventStrayRequests();
        Livewire::test(KkprlApplicantRegister::class)
            ->set('email', 'new@example.test')->set('password', 'Synthetic-new-123!')
            ->set('password_confirmation', 'Synthetic-new-123!')->call('submit')
            ->assertRedirect(route('kkprl.applicant.portal'))->assertSet('password', '');
        $this->assertDatabaseCount('kkprl_applicants', 1);
        $this->assertDatabaseCount('kkprl_proposals', 0);
        $this->get(route('kkprl.applicant.portal'))->assertOk()->assertSee('Belum ada dokumen')
            ->assertDontSee('Buat proposal baru')->assertDontSee('aria-label="Buka bantuan chat"', false);
        Http::assertNothingSent();
    }

    #[Test]
    public function legacy_start_and_resume_direct_livewire_actions_fail_closed(): void
    {
        Livewire::test(KkprlProposalStart::class)->set('phone', '081234567890')->call('submit')->assertForbidden();
        Livewire::test(KkprlProposalResume::class)->set('ticketNumber', 'KKPRL-UNKNOWN')->set('phone', '081234567890')->call('submit')->assertForbidden();
        $this->assertDatabaseCount('kkprl_proposals', 0);
    }
}
