<?php

namespace App\Services;

use App\Contracts\KkprlProposalAccessVerifier;
use App\Domain\Kkprl\InvalidProposalCredentials;
use App\Domain\Kkprl\KkprlPhoneNormalizer;
use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalAccessRateLimited;
use App\Models\KkprlProposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class KkprlProposalAccessService
{
    public const SESSION_KEY = 'kkprl_proposal_access';

    public function __construct(
        private readonly KkprlPhoneNormalizer $phones,
        private readonly KkprlProposalAccessVerifier $verifier,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function startDraft(array $input): KkprlProposal
    {
        $phone = $this->phones->normalize($input['phone'] ?? null);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor HP wajib diisi.',
            ]);
        }

        $proposal = DB::transaction(
            fn (): KkprlProposal => KkprlProposal::create([
                'phone' => $phone,
                'status' => 'draft',
            ]),
        );

        $this->grantAccess($proposal);

        return $proposal;
    }

    public function resumeDraft(string $ticketNumber, string $phone, ?string $rateLimitKey = null): KkprlProposal
    {
        $normalizedPhone = $this->phones->normalize($phone);
        $key = $this->rateLimitKey($ticketNumber, $normalizedPhone, $rateLimitKey);
        $maxAttempts = (int) config('kkprl.proposal_access.resume_attempts', 5);
        $decaySeconds = (int) config('kkprl.proposal_access.resume_decay_seconds', 900);
        $ipKey = $this->ipRateLimitKey($rateLimitKey);
        $ipMaxAttempts = (int) config('kkprl.proposal_access.resume_ip_attempts', 30);
        $ipDecaySeconds = (int) config('kkprl.proposal_access.resume_ip_decay_seconds', 900);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)
            || RateLimiter::tooManyAttempts($ipKey, $ipMaxAttempts)) {
            throw new ProposalAccessRateLimited;
        }

        $proposal = $this->verifier->verify($ticketNumber, $phone);

        if ($proposal === null) {
            RateLimiter::hit($key, $decaySeconds);
            RateLimiter::hit($ipKey, $ipDecaySeconds);
            throw new InvalidProposalCredentials;
        }

        RateLimiter::clear($key);
        $this->grantAccess($proposal);

        return $proposal;
    }

    public function current(): ?KkprlProposal
    {
        $access = session(self::SESSION_KEY);

        if (! is_array($access) || ! isset($access['proposal_id'], $access['root_proposal_id'])) {
            return null;
        }

        $lastActivity = (int) ($access['last_activity'] ?? 0);
        $timeout = (int) config('kkprl.proposal_access.idle_timeout_seconds', 1_800);

        if ($lastActivity === 0 || now()->timestamp - $lastActivity > $timeout) {
            $this->forget();

            return null;
        }

        $proposal = KkprlProposal::query()
            ->whereKey($access['proposal_id'])
            ->where('root_proposal_id', $access['root_proposal_id'])
            ->first();

        if ($proposal === null) {
            $this->forget();

            return null;
        }

        session()->put(self::SESSION_KEY.'.last_activity', now()->timestamp);

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
        session()->regenerate();
        session()->put(self::SESSION_KEY, [
            'proposal_id' => $proposal->id,
            'root_proposal_id' => $proposal->root_proposal_id,
            'last_activity' => now()->timestamp,
        ]);
    }

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private function rateLimitKey(string $ticketNumber, ?string $phone, ?string $override): string
    {
        if ($override !== null) {
            return $override;
        }

        $ip = request()->ip() ?? 'unknown';

        return 'kkprl:resume:'.hash('sha256', strtoupper(trim($ticketNumber)).'|'.$phone.'|'.$ip);
    }

    private function ipRateLimitKey(?string $override): string
    {
        if ($override !== null) {
            return $override.':ip';
        }

        return 'kkprl:resume:ip:'.hash('sha256', request()->ip() ?? 'unknown');
    }
}
