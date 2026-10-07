<?php

namespace App\Services;

use App\Models\KkprlApplicant;
use App\Models\KkprlApplicantEvent;
use App\Models\KkprlProposal;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

final class KkprlApplicantAdministrationService
{
    public function setApproval(KkprlApplicant $account, User $actor, bool $approved): void
    {
        DB::transaction(function () use ($account, $actor, $approved): void {
            $this->assertPermission($actor, 'Approve:KkprlApplicant');
            $target = KkprlApplicant::whereKey($account->id)->lockForUpdate()->firstOrFail();
            if (($target->approved_at !== null) === $approved) {
                return;
            }
            $target->forceFill(['approved_at' => $approved ? now() : null, 'approved_by' => $approved ? $actor->id : null])->save();
            $this->audit($target, $actor, $approved ? 'approved' : 'revoked');
        });
    }

    public function resetPassword(KkprlApplicant $account, User $actor, #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $confirmation): void
    {
        $this->assertPermission($actor, 'ResetPassword:KkprlApplicant');
        Validator::make(['password' => $password, 'password_confirmation' => $confirmation], [
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(12)],
        ])->validate();
        DB::transaction(function () use ($account, $actor, $password): void {
            $this->assertPermission($actor, 'ResetPassword:KkprlApplicant');
            $target = KkprlApplicant::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $target->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
                'session_version' => $target->session_version + 1,
            ])->save();
            $this->audit($target, $actor, 'password_reset');
        });
    }

    public function assignOwner(KkprlApplicant $account, KkprlProposal $proposal, User $actor, string $reason, string $verificationReference): void
    {
        $this->assertPermission($actor, 'AssignOwner:KkprlApplicant');
        Validator::make(['reason' => $reason, 'verification_reference' => $verificationReference], [
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'verification_reference' => ['required', 'string', 'min:5', 'max:2000'],
        ])->validate();
        DB::transaction(function () use ($account, $proposal, $actor, $reason, $verificationReference): void {
            $this->assertPermission($actor, 'AssignOwner:KkprlApplicant');
            $source = KkprlProposal::findOrFail($proposal->id);
            $root = KkprlProposal::whereKey($source->root_proposal_id ?: $source->id)->lockForUpdate()->firstOrFail();
            $family = KkprlProposal::where('root_proposal_id', $root->id)->orWhere('id', $root->id)->orderBy('id')->lockForUpdate()->get();
            $target = KkprlApplicant::whereKey($account->id)->lockForUpdate()->firstOrFail();
            if ($family->contains(fn ($member): bool => $member->applicant_id !== null && (int) $member->applicant_id !== $target->id)) {
                throw new AuthorizationException('Proposal already belongs to another applicant.');
            }
            // Administrative ownership does not mutate immutable payloads or document identity.
            DB::table('kkprl_proposals')->whereIn('id', $family->modelKeys())->update(['applicant_id' => $target->id]);
            $this->audit($target, $actor, 'owner_assigned', [
                'root_proposal_id' => $root->id,
                'reason' => $reason,
                'verification_reference' => $verificationReference,
            ]);
        });
    }

    private function assertPermission(User $actor, string $permission): void
    {
        if ($actor->fresh()?->can($permission) !== true) {
            throw new AuthorizationException('Permission denied.');
        }
    }

    private function audit(KkprlApplicant $account, User $actor, string $action, array $details = []): void
    {
        KkprlApplicantEvent::create([
            'applicant_id' => $account->id, 'actor_id' => $actor->id,
            'action' => $action, 'created_at' => now(),
        ] + $details);
    }
}
