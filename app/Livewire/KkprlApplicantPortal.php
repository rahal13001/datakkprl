<?php

namespace App\Livewire;

use App\Domain\Kkprl\ProposalAccessDenied;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalDocument;
use App\Services\KkprlApplicantAuthService;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalSnapshotService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.kkprl-applicant')]
class KkprlApplicantPortal extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_if(app(KkprlApplicantAuthService::class)->current() === null, 403);
    }

    public function hydrate(): void
    {
        abort_if(app(KkprlApplicantAuthService::class)->current() === null, 403);
    }

    public function createProposal(): void
    {
        try {
            app(KkprlProposalAccessService::class)->startDraft();
        } catch (ProposalAccessDenied) {
            abort(403);
        }
        $this->redirectRoute('kkprl.proposal.wizard');
    }

    public function openProposal(int $proposalId): void
    {
        $proposal = KkprlProposal::findOrFail($proposalId);
        try {
            app(KkprlProposalAccessService::class)->grantAccess($proposal);
        } catch (ProposalAccessDenied) {
            abort(403);
        }
        $this->redirectRoute('kkprl.proposal.wizard');
    }

    public function logout(): void
    {
        app(KkprlApplicantAuthService::class)->logout();
        $this->redirectRoute('kkprl.applicant.login');
    }

    public function render()
    {
        $account = app(KkprlApplicantAuthService::class)->current();
        abort_if($account === null, 403);
        $documents = KkprlProposalDocument::query()->with('proposal')
            ->whereHas('proposal', fn ($query) => $query->where('applicant_id', $account->id))
            ->where('generation_status', 'generated')->latest('generated_at')->paginate(12);
        $snapshots = app(KkprlProposalSnapshotService::class);
        $documents->setCollection($documents->getCollection()->filter(function ($document) use ($snapshots): bool {
            if ($document->storage_disk !== config('kkprl.storage_disk') || ! filled($document->storage_path)
                || ! Storage::disk($document->storage_disk)->exists($document->storage_path)) {
                return false;
            }
            try {
                $snapshot = $snapshots->capture($document->proposal, $document->chapter);
            } catch (ValidationException) {
                return false;
            }

            return hash_equals($document->snapshot_hash, $snapshot->snapshotHash)
                && hash_equals($document->attachment_manifest_hash, $snapshot->attachmentManifestHash);
        })->map(fn ($document): array => $document->only(['id', 'chapter', 'format', 'generated_at'])));

        return view('livewire.kkprl-applicant-portal', [
            'documents' => $documents,
            'approved' => $account->approved_at !== null,
            'proposals' => $account->approved_at !== null
                ? $account->proposals()->select(['id', 'ticket_number', 'revision_label', 'status'])->latest('id')->paginate(12, pageName: 'proposalsPage')
                : collect(),
        ]);
    }
}
