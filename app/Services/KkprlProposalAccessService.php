<?php

namespace App\Services;

use App\Domain\Kkprl\InvalidProposalCredentials;
use App\Domain\Kkprl\KkprlPhoneNormalizer;
use App\Domain\Kkprl\ProposalAccessDenied;
use App\Models\KkprlApplicant;
use App\Models\KkprlProposal;
use Illuminate\Support\Facades\DB;

final class KkprlProposalAccessService
{
    /** Legacy session key is never an authorization source. */
    public const SESSION_KEY = 'kkprl_proposal_access';

    public const SELECTION_KEY = 'kkprl_selected_proposal';

    public function __construct(private readonly KkprlApplicantAuthService $accounts, private readonly KkprlPhoneNormalizer $phones) {}

    public function startDraft(array $input = []): KkprlProposal
    {
        $proposal = DB::transaction(function () use ($input): KkprlProposal {
            $account = $this->assertApproved(lock: true);
            $proposal = new KkprlProposal(['phone' => $this->phones->normalize($input['phone'] ?? null), 'status' => 'draft']);
            $proposal->forceFill(['applicant_id' => $account->id])->save();

            return $proposal->fresh();
        });
        $this->grantAccess($proposal);

        return $proposal;
    }

    /** Tickets and contact numbers no longer authenticate, including direct legacy calls. */
    public function resumeDraft(string $ticketNumber, string $phone, ?string $rateLimitKey = null): KkprlProposal
    {
        throw new InvalidProposalCredentials;
    }

    public function assertApproved(bool $lock = false): KkprlApplicant
    {
        $account = $this->accounts->current($lock);
        if ($account === null || $account->approved_at === null) {
            throw new ProposalAccessDenied;
        }

        return $account;
    }

    public function assertCanBuild(KkprlProposal $proposal, bool $lock = false): void
    {
        $account = $this->assertApproved($lock || DB::transactionLevel() > 0);
        $owner = KkprlProposal::whereKey($proposal->id)->value('applicant_id');
        if ($owner === null || (int) $owner !== $account->id) {
            throw new ProposalAccessDenied;
        }
    }

    public function canReadDocuments(KkprlProposal $proposal): bool
    {
        $account = $this->accounts->current();

        return $account !== null && KkprlProposal::whereKey($proposal->id)->where('applicant_id', $account->id)->exists();
    }

    public function current(): ?KkprlProposal
    {
        $account = $this->accounts->current(DB::transactionLevel() > 0);
        $selection = session(self::SELECTION_KEY);
        if ($account === null || $account->approved_at === null || ! is_array($selection)) {
            return null;
        }
        $timeout = (int) config('kkprl.proposal_access.idle_timeout_seconds', 1800);
        if (now()->timestamp - (int) ($selection['last_activity'] ?? 0) > $timeout) {
            $this->forget();

            return null;
        }
        $proposal = KkprlProposal::whereKey($selection['proposal_id'] ?? null)->where('applicant_id', $account->id)->first();
        if ($proposal !== null) {
            session()->put(self::SELECTION_KEY.'.last_activity', now()->timestamp);
        }

        return $proposal;
    }

    public function assertCanAccess(KkprlProposal $proposal): void
    {
        $current = $this->current();
        if ($current === null || ! $current->is($proposal)) {
            throw new ProposalAccessDenied;
        }
    }

    public function grantAccess(KkprlProposal $proposal): void
    {
        $this->assertCanBuild($proposal);
        session()->forget(self::SESSION_KEY);
        session()->put(self::SELECTION_KEY, ['proposal_id' => $proposal->id, 'last_activity' => now()->timestamp]);
    }

    public function forget(): void
    {
        session()->forget([self::SESSION_KEY, self::SELECTION_KEY]);
    }
}
