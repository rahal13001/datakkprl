<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlAiChat;
use App\Livewire\KkprlApplicantPortal;
use App\Livewire\KkprlProposalWizard;
use App\Models\KkprlApplicant;
use App\Models\KkprlProposal;
use App\Services\KkprlAiService;
use App\Services\KkprlApplicantAuthService;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlApplicantAccessTest extends TestCase
{
    use RefreshDatabase;

    private function account(bool $approved = false, string $email = 'owner@example.test'): KkprlApplicant
    {
        $auth = app(KkprlApplicantAuthService::class);
        $account = $auth->register(['email' => $email, 'password' => 'Test-password-123!', 'password_confirmation' => 'Test-password-123!']);
        if ($approved) {
            $account->forceFill(['approved_at' => now()])->save();
        }
        $auth->login($email, 'Test-password-123!');

        return $account;
    }

    private function owned(KkprlApplicant $account, array $input = []): KkprlProposal
    {
        $proposal = new KkprlProposal($input);
        $proposal->forceFill(['applicant_id' => $account->id])->save();

        return $proposal->fresh();
    }

    private function payload(): array
    {
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = KkprlPayloadFixture::value($field);
            }
        }

        return $payload;
    }

    public function test_pending_portal_is_empty_without_exposing_proposal_data(): void
    {
        $account = $this->account();
        $this->owned($account, ['payload' => ['bag-1' => ['applicant_name' => 'SECRET-RAW-DRAFT']]]);
        Livewire::test(KkprlApplicantPortal::class)
            ->assertSee('Belum ada dokumen')
            ->assertDontSee('SECRET-RAW-DRAFT')
            ->assertDontSee('Buat proposal baru')
            ->assertDontSee('Pratinjau Draft')
            ->assertDontSee('Unggah')
            ->assertDontSee('progressbar');
        $this->assertDatabaseCount('kkprl_proposals', 1);
    }

    public function test_pending_can_download_generated_documents_but_not_raw_attachments_or_other_accounts(): void
    {
        Storage::fake('kkprl_private');
        $account = $this->account(true);
        $proposal = app(KkprlProposalAccessService::class)->startDraft([]);
        $proposal->update(['payload' => $this->payload()]);
        $document = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf'])->first();
        $account->forceFill(['approved_at' => null])->save();
        Livewire::test(KkprlApplicantPortal::class)->assertSee('PDF')->assertDontSee('Buat proposal baru');
        $this->get(route('kkprl.proposal.document.download', $document))->assertOk();
        $this->account(false, 'other@example.test');
        $this->get(route('kkprl.proposal.document.download', $document))->assertNotFound();
    }

    public function test_pending_cannot_create_or_mutate_draft_through_services(): void
    {
        $account = $this->account();
        $proposal = $this->owned($account);
        try {
            app(KkprlProposalAccessService::class)->startDraft(['applicant_id' => 999, 'approved_at' => now()]);
            $this->fail('Pending created a draft.');
        } catch (ProposalAccessDenied) {
            $this->assertDatabaseCount('kkprl_proposals', 1);
        }
        $this->expectException(ProposalAccessDenied::class);
        app(KkprlProposalDraftService::class)->save($proposal, ['bag-1' => ['applicant_name' => 'INJECTED']], 1);
    }

    public function test_pending_cannot_mount_wizard_or_chat_in_direct_livewire_requests(): void
    {
        Http::preventStrayRequests();
        $proposal = $this->owned($this->account());
        session()->put('kkprl_proposal_access', ['proposal_id' => $proposal->id, 'root_proposal_id' => $proposal->id, 'last_activity' => now()->timestamp]);
        Livewire::test(KkprlProposalWizard::class)->assertForbidden();
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])->assertForbidden();
        Http::assertNothingSent();
        $this->assertDatabaseCount('kkprl_proposal_chats', 0);
    }

    public function test_legacy_session_and_ticket_routes_do_not_authenticate(): void
    {
        $proposal = KkprlProposal::create(['phone' => '081234567890']);
        session()->put('kkprl_proposal_access', ['proposal_id' => $proposal->id, 'root_proposal_id' => $proposal->id, 'last_activity' => now()->timestamp]);
        $this->assertNull(app(KkprlProposalAccessService::class)->current());
        $this->get('/buat-proposal-kkprl')->assertRedirect(route('kkprl.applicant.login'));
        $this->get('/lanjutkan-proposal-kkprl?ticket='.$proposal->ticket_number.'&phone=081234567890')->assertRedirect(route('kkprl.applicant.login'));
        $this->assertNull(app(KkprlApplicantAuthService::class)->current());
    }

    public function test_approved_creates_owned_draft_without_owner_injection_and_can_use_wizard(): void
    {
        $account = $this->account(true);
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['applicant_id' => 999, 'owner_id' => 999, 'status' => 'submitted']);
        $this->assertSame($account->id, $proposal->applicant_id);
        $this->assertSame('draft', $proposal->status);
        Livewire::test(KkprlProposalWizard::class)->set('payload.bag-1.applicant_name', 'Owned draft')->call('saveDraft');
        $this->assertSame('Owned draft', $proposal->fresh()->payload['bag-1']['applicant_name']);
    }

    public function test_cross_account_builder_selection_is_rejected(): void
    {
        $first = $this->account(true);
        $proposal = $this->owned($first);
        $this->account(true, 'second@example.test');
        $this->expectException(ProposalAccessDenied::class);
        app(KkprlProposalAccessService::class)->grantAccess($proposal);
    }

    public function test_revoked_account_cannot_use_a_previously_mounted_livewire_chat(): void
    {
        $account = $this->account(true);
        $proposal = app(KkprlProposalAccessService::class)->startDraft([]);
        $component = Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3']);
        $account->forceFill(['approved_at' => null])->save();
        $component->call('sendMessage')->assertForbidden();
        $this->assertDatabaseCount('kkprl_proposal_chats', 0);
    }

    public function test_approval_revoked_during_ai_prevents_persistence(): void
    {
        $account = $this->account(true);
        $proposal = app(KkprlProposalAccessService::class)->startDraft([]);
        $ai = \Mockery::mock(KkprlAiService::class);
        $ai->shouldReceive('chat')->once()->andReturnUsing(function () use ($account): string {
            KkprlApplicant::whereKey($account->id)->update(['approved_at' => null]);

            return 'Narasi setelah approval dicabut.';
        });
        $this->app->instance(KkprlAiService::class, $ai);
        Livewire::test(KkprlAiChat::class, ['proposalId' => $proposal->id, 'chapter' => 'bag-3'])
            ->set('userInput', 'Ulas kondisi perairan')->call('sendMessage')->assertForbidden();
        $this->assertDatabaseCount('kkprl_proposal_chats', 0);
        $this->assertEmpty($proposal->fresh()->payload);
    }
}
