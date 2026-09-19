<?php

namespace Tests\Feature;

use App\Contracts\KkprlProposalAccessVerifier;
use App\Domain\Kkprl\InvalidProposalCredentials;
use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalAccessRateLimited;
use App\Models\KkprlProposal;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlTicketPhoneAccessVerifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KkprlProposalAccessTest extends TestCase
{
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
        $this->assertSame($proposal->id, session('kkprl_proposal_access.proposal_id'));
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
    public function applicant_can_resume_with_equivalent_phone_format(): void
    {
        $service = app(KkprlProposalAccessService::class);
        $proposal = $service->startDraft(['phone' => '0812-3456-7890']);
        $service->forget();

        $resumed = $service->resumeDraft($proposal->ticket_number, '+62 812 3456 7890');

        $this->assertTrue($resumed->is($proposal));
        $this->assertSame($proposal->id, session('kkprl_proposal_access.proposal_id'));
    }

    #[Test]
    public function main_ticket_can_resume_a_submitted_proposal_without_error(): void
    {
        $service = app(KkprlProposalAccessService::class);
        $proposal = KkprlProposal::create([
            'phone' => '0812-3456-7890',
            'status' => 'submitted',
        ]);

        $resumed = $service->resumeDraft($proposal->ticket_number, '0812-3456-7890');

        $this->assertTrue($resumed->is($proposal));
        $this->assertTrue($resumed->isLocked());
        $this->assertSame($proposal->id, session('kkprl_proposal_access.proposal_id'));
    }

    #[Test]
    public function wrong_credentials_use_generic_error(): void
    {
        $this->expectException(InvalidProposalCredentials::class);
        $this->expectExceptionMessage('Credential verification failed.');

        app(KkprlProposalAccessService::class)->resumeDraft('KKPRL-UNKNOWN', '0812-0000-0000');
    }

    #[Test]
    public function repeated_resume_attempts_are_rate_limited(): void
    {
        $service = app(KkprlProposalAccessService::class);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                $service->resumeDraft('KKPRL-UNKNOWN', '0812-0000-0000', 'kkprl-test');
            } catch (InvalidProposalCredentials) {
                // Expected until the configured limit is reached.
            }
        }

        $this->expectException(ProposalAccessRateLimited::class);
        $service->resumeDraft('KKPRL-UNKNOWN', '0812-0000-0000', 'kkprl-test');
    }

    #[Test]
    public function varying_bad_credentials_are_rate_limited_per_ip(): void
    {
        config([
            'kkprl.proposal_access.resume_ip_attempts' => 2,
            'kkprl.proposal_access.resume_ip_decay_seconds' => 900,
        ]);
        $ipKey = 'kkprl:resume:ip:'.hash('sha256', request()->ip() ?? 'unknown');
        RateLimiter::clear($ipKey);
        $service = app(KkprlProposalAccessService::class);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $service->resumeDraft('KKPRL-UNKNOWN-'.$attempt, '0812-0000-000'.$attempt);
            } catch (InvalidProposalCredentials) {
                // Expected until the IP limit is reached.
            }
        }

        $this->expectException(ProposalAccessRateLimited::class);
        $service->resumeDraft('KKPRL-UNKNOWN-3', '0812-0000-0003');
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
