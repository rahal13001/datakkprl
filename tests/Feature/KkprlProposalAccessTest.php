<?php

namespace Tests\Feature;

use App\Contracts\KkprlProposalAccessVerifier;
use App\Domain\Kkprl\InvalidProposalCredentials;
use App\Domain\Kkprl\ProposalAccessDenied;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlTicketPhoneAccessVerifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\KkprlApplicantFixture;
use Tests\TestCase;

class KkprlProposalAccessTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('kkprl-test');
        RateLimiter::clear('kkprl-test:ip');
    }

    #[Test]
    public function public_access_uses_a_replaceable_credential_verifier(): void
    {
        $this->assertInstanceOf(
            KkprlTicketPhoneAccessVerifier::class,
            app(KkprlProposalAccessVerifier::class),
        );
    }

    #[Test]
    public function applicant_can_create_draft_and_receive_session_access(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft([
            'phone' => '0812-3456-7890',
            'applicant_name' => 'Pemohon Uji',
        ]);

        $this->assertSame('draft', $proposal->status);
        $this->assertSame($proposal->id, session('kkprl_selected_proposal.proposal_id'));
        $this->assertTrue(app(KkprlProposalAccessService::class)->current()->is($proposal));
    }

    #[Test]
    public function draft_creation_whitelists_public_input_and_cannot_set_payload_or_status(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft([
            'phone' => '0812-3456-7890',
            'status' => 'submitted',
            'payload' => ['bag-1' => ['internal' => 'ignored']],
        ]);

        $this->assertSame('draft', $proposal->status);
        $this->assertSame([], $proposal->payload ?? []);
    }

    #[Test]
    public function ticket_phone_cannot_resume_even_an_owned_proposal(): void
    {
        $service = app(KkprlProposalAccessService::class);
        $proposal = $service->startDraft(['phone' => '0812-3456-7890']);
        $service->forget();
        $this->assertNull(app(KkprlProposalAccessVerifier::class)->verify($proposal->ticket_number, '+62 812 3456 7890'));
        $this->expectException(InvalidProposalCredentials::class);
        $service->resumeDraft($proposal->ticket_number, '+62 812 3456 7890');
    }

    #[Test]
    public function owned_submitted_proposal_can_be_selected_but_remains_locked(): void
    {
        $service = app(KkprlProposalAccessService::class);
        $proposal = $this->ownedProposal(['status' => 'submitted']);
        $service->grantAccess($proposal);
        $this->assertTrue($service->current()->is($proposal));
        $this->assertTrue($service->current()->isLocked());
    }

    #[Test]
    public function wrong_credentials_use_generic_error(): void
    {
        $this->expectException(InvalidProposalCredentials::class);
        $this->expectExceptionMessage('Credential verification failed.');

        app(KkprlProposalAccessService::class)->resumeDraft('KKPRL-UNKNOWN', '0812-0000-0000');
    }

    #[Test]
    public function repeated_and_varying_legacy_credentials_never_create_access(): void
    {
        $service = app(KkprlProposalAccessService::class);
        $service->forget();
        for ($attempt = 0; $attempt < 8; $attempt++) {
            try {
                $service->resumeDraft('KKPRL-UNKNOWN-'.$attempt, '08120000000'.$attempt);
                $this->fail('Legacy access must fail closed.');
            } catch (InvalidProposalCredentials) {
                $this->assertNull($service->current());
            }
        }
    }

    #[Test]
    public function session_cannot_access_another_proposal(): void
    {
        $service = app(KkprlProposalAccessService::class);
        $first = $service->startDraft(['phone' => '0812-1111-1111']);
        $second = $service->startDraft(['phone' => '0812-2222-2222']);
        $service->grantAccess($first);

        $this->expectException(ProposalAccessDenied::class);
        $service->assertCanAccess($second);
    }

    #[Test]
    public function idle_session_expires(): void
    {
        config(['kkprl.proposal_access.idle_timeout_seconds' => 60]);
        Carbon::setTestNow('2026-08-24 10:00:00');

        $service = app(KkprlProposalAccessService::class);
        $service->startDraft(['phone' => '0812-3333-3333']);

        Carbon::setTestNow('2026-08-24 10:01:01');

        $this->assertNull($service->current());
        $this->assertNull(session('kkprl_proposal_access'));
        Carbon::setTestNow();
    }
}
