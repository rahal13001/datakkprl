<?php

namespace App\Services;

use App\Domain\Kkprl\ProposalChapterRules;
use App\Domain\Kkprl\ProposalLocked;
use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalEditSession;
use App\Models\KkprlProposalReviewEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class KkprlProposalSubmissionService
{
    public function __construct(
        private readonly ProposalProgress $progress,
        private readonly ProposalChapterRules $chapterRules,
        private readonly KkprlProposalDocumentService $documents,
    ) {}

    public function submit(KkprlProposal $proposal): KkprlProposal
    {
        return DB::transaction(function () use ($proposal): KkprlProposal {
            $locked = KkprlProposal::query()->lockForUpdate()->findOrFail($proposal->id);

            if ($locked->isLocked()) {
                throw new ProposalLocked;
            }

            $result = $this->progress->evaluate($locked->payload ?? []);
            $errors = [];

            foreach ($result->chapters as $chapter => $state) {
                if ($state['status'] !== 'complete') {
                    $errors['chapters.'.$chapter] = 'Bab '.str_replace('bag-', '', $chapter).' belum lengkap.';
                }
            }

            foreach ($result->errors as $chapter => $issues) {
                foreach ($issues as $issue) {
                    $errors[$issue] = 'Isian '.$issue.' pada '.$chapter.' tidak valid.';
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            foreach ($this->chapterRules->relevantChapters($locked->payload ?? []) as $chapter) {
                $this->documents->generateChapterFormats($locked, $chapter);
            }

            $payload = $locked->payload ?? [];
            $locked->status = 'submitted';
            $locked->generated_at = now();
            $locked->submitted_at = now();
            $locked->save();
            KkprlProposalReviewEvent::create([
                'proposal_id' => $locked->id,
                'event_type' => 'submitted',
                'actor_type' => 'applicant',
                'actor_id' => null,
                'reason' => 'Proposal dikirim untuk ditinjau.',
                'before_payload' => $payload,
                'after_payload' => $payload,
                'metadata' => ['status' => 'submitted'],
                'request_id' => request()->header('X-Request-ID'),
                'created_at' => now(),
            ]);
            $sessions = KkprlProposalEditSession::query()
                ->where('status', 'active')
                ->where(function ($query) use ($proposal): void {
                    $query->where('proposal_id', $proposal->id)
                        ->orWhere('revision_id', $proposal->id);
                })
                ->get();
            foreach ($sessions as $session) {
                $session->update([
                    'status' => 'ended',
                    'session_finished_at' => now(),
                    'ended_at' => now(),
                    'end_reason' => 'submitted',
                ]);
                KkprlProposalReviewEvent::create([
                    'proposal_id' => $locked->id,
                    'event_type' => 'edit_session_submitted',
                    'actor_type' => 'system',
                    'actor_id' => null,
                    'reason' => 'Proposal submitted.',
                    'metadata' => ['edit_session_id' => $session->id],
                    'request_id' => request()->header('X-Request-ID'),
                    'created_at' => now(),
                ]);
            }

            return $locked->fresh();
        });
    }
}
