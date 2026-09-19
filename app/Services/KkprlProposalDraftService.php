<?php

namespace App\Services;

use App\Domain\Kkprl\ProposalLocked;
use App\Domain\Kkprl\ProposalPayloadSanitizer;
use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposal;
use Illuminate\Support\Facades\DB;

final class KkprlProposalDraftService
{
    public function __construct(
        private readonly ProposalPayloadSanitizer $sanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function save(KkprlProposal $proposal, array $payload, int $step): KkprlProposal
    {
        if ($proposal->isLocked()) {
            throw new ProposalLocked;
        }

        $payload = $this->sanitizer->sanitize($payload);
        $progress = app(ProposalProgress::class)->evaluate($payload);
        $bagOne = is_array($payload['bag-1'] ?? null) ? $payload['bag-1'] : [];

        return DB::transaction(function () use ($proposal, $payload, $progress, $step, $bagOne): KkprlProposal {
            $lockedProposal = KkprlProposal::query()->lockForUpdate()->findOrFail($proposal->id);

            if ($lockedProposal->isLocked()) {
                throw new ProposalLocked;
            }

            $lockedProposal->fill([
                'payload' => $payload,
                'current_step' => max(1, $step),
                'progress_percent' => $progress->percent,
                'last_saved_at' => now(),
                'applicant_name' => $bagOne['applicant_name'] ?? null,
                'institution_name' => $bagOne['institution_name'] ?? null,
                'province' => $bagOne['province'] ?? null,
                'regency' => $bagOne['regency'] ?? null,
                'activity_type' => is_scalar($bagOne['main_activity'] ?? null)
                    ? mb_substr((string) $bagOne['main_activity'], 0, 32)
                    : null,
            ]);
            $lockedProposal->save();

            return $lockedProposal->fresh();
        });
    }
}
