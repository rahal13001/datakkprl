<?php

namespace App\Livewire;

use App\Domain\Kkprl\AttachmentQuotaViolation;
use App\Domain\Kkprl\InvalidAttachmentFile;
use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalLocked;
use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalAttachment;
use App\Models\KkprlProposalDocument;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalDraftService;
use App\Services\KkprlProposalSnapshotService;
use App\Services\KkprlProposalSubmissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class KkprlProposalWizard extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $proposalId;

    public int $currentStep = 1;

    /** @var array<string, mixed> */
    public array $payload = [];

    /** @var array<string, array{status: string, completed: int, total: int}> */
    public array $chapterProgress = [];

    /** @var list<string> */
    public array $relevantChapters = [];

    public bool $saved = false;

    public bool $locked = false;

    /** @var list<UploadedFile> */
    public array $pendingFiles = [];

    public string $attachmentPlacement = 'appendix';

    public string $attachmentAnchor = '';

    public string $attachmentCaption = '';

    public string $attachmentMessage = '';

    public ?int $replacementAttachmentId = null;

    /** @var UploadedFile|null */
    public $replacementFile = null;

    public string $attachmentChapter = 'bag-1';

    public string $documentMessage = '';

    public string $lastSavedAt = '';

    public string $revisionNote = '';

    public function mount(KkprlProposalAccessService $access, ProposalProgress $progress): void
    {
        $proposal = $access->current();
        abort_if($proposal === null, 403);

        $this->proposalId = $proposal->id;
        $this->payload = $proposal->payload ?? [];
        $this->lastSavedAt = $proposal->last_saved_at?->format('d-m-Y H:i:s') ?? '';
        $this->currentStep = max(1, $proposal->current_step);
        $this->locked = $proposal->isLocked();
        $revisionOf = $proposal->revisionOf;
        $this->revisionNote = (string) ($revisionOf?->reviewEvents()
            ->where('event_type', 'needs_revision')
            ->latest('id')
            ->value('reason') ?? '');
        $this->refreshProgress($progress);
        $this->normalizeStep();
        $this->syncAttachmentChapter();
    }

    #[On('draft-saved')]
    public function refreshState(): void
    {
        $proposal = KkprlProposal::find($this->proposalId);
        if ($proposal) {
            $this->payload = $proposal->payload ?? [];
            $this->lastSavedAt = $proposal->last_saved_at?->format('d-m-Y H:i:s') ?? '';
        }
    }

    public function updatedPayload(ProposalProgress $progress): void
    {
        $this->persist(app(KkprlProposalDraftService::class), $progress);
    }

    public function saveDraft(
        KkprlProposalAccessService $access,
        KkprlProposalDraftService $drafts,
        ProposalProgress $progress,
    ): void {
        $this->persist($drafts, $progress, $access);
    }

    public function next(
        KkprlProposalAccessService $access,
        KkprlProposalDraftService $drafts,
        ProposalProgress $progress,
    ): void {
        $this->persist($drafts, $progress, $access);
        $this->currentStep = min(count($this->relevantChapters), $this->currentStep + 1);
        $this->syncAttachmentChapter();
        $this->saved = false;
    }

    public function previous(): void
    {
        $this->currentStep = max(1, $this->currentStep - 1);
        $this->syncAttachmentChapter();
        $this->saved = false;
    }

    public function addScheduleRow(
        KkprlProposalDraftService $drafts,
        ProposalProgress $progress,
    ): void {
        $this->payload['bag-4']['reclamation_schedule_rows'] ??= [];
        $this->payload['bag-4']['reclamation_schedule_rows'][] = [
            'activity' => '',
            'start_date' => '',
            'end_date' => '',
            'notes' => '',
        ];
        $this->persist($drafts, $progress);
    }

    public function removeScheduleRow(
        int $index,
        KkprlProposalDraftService $drafts,
        ProposalProgress $progress,
    ): void {
        $rows = $this->payload['bag-4']['reclamation_schedule_rows'] ?? [];

        if (count($rows) <= 1 || ! array_key_exists($index, $rows)) {
            return;
        }

        array_splice($rows, $index, 1);
        $this->payload['bag-4']['reclamation_schedule_rows'] = array_values($rows);
        $this->persist($drafts, $progress);
    }

    public function uploadAttachments(
        KkprlProposalAccessService $access,
        KkprlProposalAttachmentService $attachments,
    ): void {
        $this->resetErrorBag('attachments');
        $proposal = KkprlProposal::query()->findOrFail($this->proposalId);

        try {
            $access->assertCanAccess($proposal);

            $attachments->storeMany($proposal, $this->attachmentChapter, $this->pendingFiles, [
                'placement' => $this->attachmentPlacement,
                'anchor_key' => $this->attachmentAnchor ?: null,
                'caption' => $this->attachmentCaption ?: null,
            ]);

            $this->reset('pendingFiles');
            $this->attachmentMessage = 'Lampiran berhasil diunggah.';
        } catch (AttachmentQuotaViolation $exception) {
            $message = $exception->kind === 'attachment_count'
                ? 'Jumlah lampiran melebihi maksimum 10 file per bab.'
                : 'Total ukuran lampiran melebihi 15 MiB per bab.';
            $this->addError('attachments', $message);
        } catch (InvalidAttachmentFile $exception) {
            $msg = $exception->getMessage();
            if (empty($msg)) {
                $msg = 'File ditolak. Gunakan PDF, JPG/JPEG, PNG, atau WebP dan anchor inline yang valid.';
            }
            $this->addError('attachments', $msg);
        } catch (ProposalLocked) {
            $this->addError('attachments', 'Proposal sudah terkunci dan lampiran tidak dapat diubah.');
        } catch (ProposalAccessDenied) {
            $this->addError('attachments', 'Sesi akses proposal berakhir. Verifikasi ulang nomor tiket dan nomor HP.');
        }
    }

    public function deleteAttachment(
        int $attachmentId,
        KkprlProposalAccessService $access,
        KkprlProposalAttachmentService $attachments,
    ): void {
        $this->resetErrorBag('attachments');
        $proposal = KkprlProposal::query()->findOrFail($this->proposalId);

        try {
            $access->assertCanAccess($proposal);
            $attachment = $proposal->attachments()->whereKey($attachmentId)->firstOrFail();
            $attachments->delete($proposal, $attachment);
            $this->attachmentMessage = 'Lampiran dihapus.';
        } catch (ProposalLocked) {
            $this->addError('attachments', 'Proposal sudah terkunci dan lampiran tidak dapat diubah.');
        } catch (ProposalAccessDenied) {
            $this->addError('attachments', 'Sesi akses proposal berakhir. Verifikasi ulang nomor tiket dan nomor HP.');
        }
    }

    public function replaceAttachment(
        KkprlProposalAccessService $access,
        KkprlProposalAttachmentService $attachments,
    ): void {
        $this->resetErrorBag('attachments');
        $proposal = KkprlProposal::query()->findOrFail($this->proposalId);

        if (! $this->replacementAttachmentId || $this->replacementFile === null) {
            $this->addError('attachments', 'Pilih lampiran yang akan diganti dan file penggantinya.');

            return;
        }

        try {
            $access->assertCanAccess($proposal);
            $attachment = $proposal->attachments()->whereKey($this->replacementAttachmentId)->firstOrFail();
            $attachments->replace($proposal, $attachment, $this->replacementFile);
            $this->reset('replacementAttachmentId', 'replacementFile');
            $this->attachmentMessage = 'Lampiran diganti.';
        } catch (AttachmentQuotaViolation $exception) {
            $message = $exception->kind === 'attachment_count'
                ? 'Jumlah lampiran melebihi maksimum 10 file per bab.'
                : 'Total ukuran lampiran melebihi 15 MiB per bab.';
            $this->addError('attachments', $message);
        } catch (InvalidAttachmentFile $exception) {
            $msg = $exception->getMessage();
            if (empty($msg)) {
                $msg = 'File ditolak. Gunakan PDF, JPG/JPEG, PNG, atau WebP dan anchor inline yang valid.';
            }
            $this->addError('attachments', $msg);
        } catch (ProposalLocked) {
            $this->addError('attachments', 'Proposal sudah terkunci dan lampiran tidak dapat diubah.');
        } catch (ProposalAccessDenied) {
            $this->addError('attachments', 'Sesi akses proposal berakhir. Verifikasi ulang nomor tiket dan nomor HP.');
        }
    }

    public function submitProposal(
        KkprlProposalAccessService $access,
        KkprlProposalSubmissionService $submission,
    ): void {
        $this->resetErrorBag();

        try {
            $proposal = KkprlProposal::query()->findOrFail($this->proposalId);
            $access->assertCanAccess($proposal);
            $submission->submit($proposal);
            $this->locked = true;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->errors());
        } catch (ProposalLocked) {
            $this->locked = true;
        } catch (ProposalAccessDenied) {
            $this->addError('draft', 'Sesi akses proposal berakhir. Verifikasi ulang nomor tiket dan nomor HP.');
        } catch (\Throwable) {
            $this->addError('documents', 'Dokumen belum berhasil dibuat. Proposal tetap draft; coba lagi setelah lampiran atau data diperiksa.');
        }
    }

    public function generateChapterDocuments(
        string $chapter,
        KkprlProposalAccessService $access,
        KkprlProposalDocumentService $documents,
    ): void {
        $this->resetErrorBag('documents');
        $proposal = KkprlProposal::query()->findOrFail($this->proposalId);

        try {
            $access->assertCanAccess($proposal);
            $documents->generateChapterFormats($proposal->fresh(), $chapter);
            $this->documentMessage = 'Word dan PDF '.$chapter.' berhasil dibuat.';
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->errors());
        } catch (ProposalAccessDenied) {
            $this->addError('documents', 'Sesi akses proposal berakhir. Verifikasi ulang nomor tiket dan nomor HP.');
        } catch (\Throwable) {
            $this->addError('documents', 'Dokumen belum dapat dibuat. Periksa kelengkapan bab dan lampiran.');
        }
    }

    public function render()
    {
        $proposal = KkprlProposal::query()->findOrFail($this->proposalId);
        try {
            app(KkprlProposalAccessService::class)->assertCanAccess($proposal);
        } catch (ProposalAccessDenied) {
            return view('livewire.kkprl-proposal-access-expired');
        }

        $snapshots = app(KkprlProposalSnapshotService::class);
        $generatedDocuments = KkprlProposalDocument::query()
            ->where('proposal_id', $this->proposalId)
            ->where('generation_status', 'generated')
            ->latest('generated_at')
            ->get()
            ->filter(function (KkprlProposalDocument $document) use ($proposal, $snapshots): bool {
                try {
                    $snapshot = $snapshots->capture($proposal, $document->chapter);
                } catch (ValidationException) {
                    return false;
                }

                return hash_equals((string) $document->snapshot_hash, $snapshot->snapshotHash)
                    && hash_equals((string) $document->attachment_manifest_hash, $snapshot->attachmentManifestHash);
            })
            ->values();

        return view('livewire.kkprl-proposal-wizard', [
            'currentChapter' => $this->relevantChapters[$this->currentStep - 1] ?? 'bag-1',
            'generatedDocuments' => $generatedDocuments,
            'chapterAttachments' => KkprlProposalAttachment::query()
                ->where('proposal_id', $this->proposalId)
                ->where('chapter', $this->relevantChapters[$this->currentStep - 1] ?? 'bag-1')
                ->orderBy('display_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function updateCoordinates(string $raw, string $text, string $shapeType = 'polygon'): void
    {
        \Illuminate\Support\Facades\Log::info('updateCoordinates CALLED', ['raw' => $raw, 'shape' => $shapeType]);
        
        $bag = $this->payload['bag-1'] ?? [];
        $bag['coordinates_raw'] = $raw;
        $bag['coordinates'] = $text;
        $bag['shape_type'] = $shapeType;
        $this->payload['bag-1'] = $bag;
    }

    private function persist(
        KkprlProposalDraftService $drafts,
        ProposalProgress $progress,
        ?KkprlProposalAccessService $access = null,
    ): void {
        try {
            $proposal = KkprlProposal::query()->findOrFail($this->proposalId);
            ($access ?? app(KkprlProposalAccessService::class))->assertCanAccess($proposal);
            
            \Illuminate\Support\Facades\Log::info('BEFORE SAVE', ['bag-1' => $this->payload['bag-1'] ?? []]);
            
            $saved = $drafts->save($proposal, $this->payload, $this->currentStep);
            
            \Illuminate\Support\Facades\Log::info('AFTER SAVE', ['bag-1' => $saved->payload['bag-1'] ?? []]);
            
            $this->payload = $saved->payload ?? [];
            $this->lastSavedAt = $saved->last_saved_at?->format('d-m-Y H:i:s') ?? '';
            $this->refreshProgress($progress);
            $this->syncAttachmentChapter();
            $this->saved = true;
            $this->resetErrorBag();
        } catch (ProposalLocked) {
            $this->addError('draft', 'Proposal sudah terkunci dan tidak dapat diubah.');
        } catch (ProposalAccessDenied) {
            $this->addError('draft', 'Sesi akses proposal berakhir. Verifikasi ulang nomor tiket dan nomor HP.');
        }
    }

    private function refreshProgress(ProposalProgress $progress): void
    {
        $result = $progress->evaluate($this->payload);
        $this->chapterProgress = $result->chapters;
        $this->relevantChapters = $result->relevantChapters;
        $this->normalizeStep();
    }

    private function normalizeStep(): void
    {
        $this->currentStep = min(max(1, $this->currentStep), max(1, count($this->relevantChapters)));
    }

    private function syncAttachmentChapter(): void
    {
        $this->attachmentChapter = $this->relevantChapters[$this->currentStep - 1] ?? 'bag-1';
    }
}
