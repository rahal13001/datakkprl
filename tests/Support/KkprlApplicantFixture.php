<?php

namespace Tests\Support;

use App\Models\KkprlApplicant;
use App\Models\KkprlProposal;
use App\Services\KkprlApplicantAuthService;
use Illuminate\Support\Facades\Auth;

trait KkprlApplicantFixture
{
    protected KkprlApplicant $applicant;

    protected function setUpKkprlApplicantFixture(): void
    {
        $this->applicant = KkprlApplicant::create([
            'email' => 'fixture@example.test',
            'password' => 'Synthetic-fixture-123!',
        ]);
        $this->applicant->forceFill(['approved_at' => now()])->save();
        $this->signInApplicant($this->applicant->fresh());
    }

    protected function signInApplicant(KkprlApplicant $account): void
    {
        Auth::guard(KkprlApplicantAuthService::GUARD)->login($account);
        session()->put(KkprlApplicantAuthService::VERSION_KEY, $account->session_version);
        session()->forget(['kkprl_proposal_access', 'kkprl_selected_proposal']);
    }

    protected function ownedProposal(array $attributes): KkprlProposal
    {
        $proposal = new KkprlProposal($attributes);
        $proposal->forceFill(['applicant_id' => $this->applicant->id])->save();

        return $proposal->fresh();
    }
}
