<?php

namespace App\Livewire;

use App\Domain\Kkprl\AttachmentQuotaViolation;
use App\Domain\Kkprl\InvalidAttachmentFile;
use App\Domain\Kkprl\ProposalAccessDenied;
use App\Domain\Kkprl\ProposalFieldCatalog;
use App\Domain\Kkprl\ProposalLocked;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\Concerns\WithKkprlFileUploads;
use App\Models\KkprlProposal;
use App\Models\KkprlProposalAttachment;
use App\Models\KkprlProposalDocument;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentDeletionService;
use App\Services\KkprlProposalDocumentListingService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalDraftService;
use App\Services\KkprlProposalSubmissionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KkprlProposalWizard extends Component
{
    use WithKkprlFileUploads;

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
        $this->payload = $this->editingPayload($proposal->payload ?? []);
        $this->normalizeConditionInputs();
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

    public function hydrate(): void
    {
        try {
            app(KkprlProposalAccessService::class)->assertCanAccess(KkprlProposal::findOrFail($this->proposalId));
        } catch (ProposalAccessDenied) {
            abort(403);
        }
    }

    #[On('draft-saved')]
    public function refreshState(ProposalProgress $progress): void
    {
        $proposal = KkprlProposal::find($this->proposalId);
        if ($proposal) {
            try {
                app(KkprlProposalAccessService::class)->assertCanAccess($proposal);
            } catch (ProposalAccessDenied) {
                return;
            }
            $this->payload = $this->editingPayload($proposal->payload ?? []);
            $this->normalizeConditionInputs();
            $this->lastSavedAt = $proposal->last_saved_at?->format('d-m-Y H:i:s') ?? '';
            $this->locked = $proposal->isLocked();
            $this->refreshProgress($progress);
            $this->syncAttachmentChapter();
        }
    }

    public function updatedPayload(ProposalProgress $progress): void
    {
        $this->normalizeConditionInputs();
        $this->persist(app(KkprlProposalDraftService::class), $progress);
    }

    public function updatedCurrentStep(): void
    {
        $this->normalizeStep();
        $this->syncAttachmentChapter();
        $this->saved = false;
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
            $this->addError('attachments', 'Akses builder berakhir. Buka akun proposal dan periksa approval Anda.');
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
            $this->addError('attachments', 'Akses builder berakhir. Buka akun proposal dan periksa approval Anda.');
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
            $this->addError('attachments', 'Akses builder berakhir. Buka akun proposal dan periksa approval Anda.');
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
            $this->addError('draft', 'Akses builder berakhir. Buka akun proposal dan periksa approval Anda.');
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
            $this->addError('documents', 'Akses builder berakhir. Buka akun proposal dan periksa approval Anda.');
        } catch (\Throwable) {
            $this->addError('documents', 'Dokumen belum dapat dibuat. Periksa kelengkapan bab dan lampiran.');
        }
    }

    public function deleteDocument(int $documentId, KkprlProposalDocumentDeletionService $deletion): void
    {
        $this->resetErrorBag('documents');
        $this->documentMessage = '';
        $proposal = KkprlProposal::findOrFail($this->proposalId);
        app(KkprlProposalAccessService::class)->assertCanAccess($proposal);
        abort_unless($proposal->documents()->whereKey($documentId)->exists(), 404);
        try {
            $deletion->delete($documentId);
            $this->documentMessage = 'Dokumen dihapus. Data proposal dan lampiran tetap tersimpan.';
        } catch (ProposalAccessDenied) {
            abort(403);
        } catch (ModelNotFoundException) {
            abort(404);
        } catch (ProposalLocked) {
            $this->addError('documents', 'Dokumen final tidak dapat dihapus. Arsip proposal tetap tersimpan.');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->errors());
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

        $listing = app(KkprlProposalDocumentListingService::class);
        $generatedDocuments = KkprlProposalDocument::query()
            ->where('proposal_id', $this->proposalId)
            ->whereNull('deleted_at')
            ->where('generation_status', 'generated')
            ->latest('generated_at')
            ->latest('id')
            ->get()
            ->map(fn (KkprlProposalDocument $document): array => $listing->present($document, $proposal, true))
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

    public function updateCoordinates(
        string $raw,
        string $text,
        string $shapeType = 'polygon',
    ): void {
        $proposal = KkprlProposal::query()->findOrFail($this->proposalId);
        app(KkprlProposalAccessService::class)->assertCanAccess($proposal);
        if ($proposal->isLocked()) {
            throw new ProposalLocked;
        }

        $bag = $this->payload['bag-1'] ?? [];
        $bag['coordinates_raw'] = $raw;
        $bag['coordinates'] = $text;
        $bag['shape_type'] = $shapeType;
        $this->payload['bag-1'] = $bag;
        $this->persist(app(KkprlProposalDraftService::class), app(ProposalProgress::class));
    }

    private function persist(
        KkprlProposalDraftService $drafts,
        ProposalProgress $progress,
        ?KkprlProposalAccessService $access = null,
    ): void {
        try {
            $proposal = KkprlProposal::query()->findOrFail($this->proposalId);
            ($access ?? app(KkprlProposalAccessService::class))->assertCanAccess($proposal);

            $saved = $drafts->save($proposal, $this->payloadForStorage($proposal), $this->currentStep);

            // Livewire replaces an object when its key order changes. Keep the
            // input order so an autosave response preserves edits made in flight.
            $sanitized = $this->editingPayload($saved->payload ?? []);
            $this->payload = array_replace(array_intersect_key($this->payload, $sanitized), $sanitized);
            $this->lastSavedAt = $saved->last_saved_at?->format('d-m-Y H:i:s') ?? '';
            $this->refreshProgress($progress);
            $this->syncAttachmentChapter();
            $this->saved = true;
            $this->resetErrorBag();
        } catch (ProposalLocked) {
            $this->addError('draft', 'Proposal sudah terkunci dan tidak dapat diubah.');
        } catch (ProposalAccessDenied) {
            $this->addError('draft', 'Akses builder berakhir. Buka akun proposal dan periksa approval Anda.');
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

    private function normalizeConditionInputs(): void
    {
        foreach (['includes_reclamation', 'has_existing_permits'] as $field) {
            if (in_array($this->payload[$field] ?? null, [true, 1, '1', 'true'], true)) {
                $this->payload[$field] = '1';
            } elseif (in_array($this->payload[$field] ?? null, [false, 0, '0', 'false'], true)) {
                $this->payload[$field] = '0';
            }
        }
    }

    /** Keep empty inputs as object keys so the first response cannot replace local edits. */
    private function editingPayload(array $payload): array
    {
        $conditions = ['includes_reclamation', 'land_relation', 'has_existing_permits'];
        foreach (['bag-1', 'bag-2', 'bag-3', 'bag-4', 'bag-5'] as $chapter) {
            if (array_key_exists($chapter, $payload) && ! is_array($payload[$chapter])) {
                unset($payload[$chapter]);
            }
            $fields = $chapter === 'bag-5'
                ? array_merge(ProposalFieldCatalog::fields('bag-5-land'), ProposalFieldCatalog::fields('bag-5-permits'))
                : ProposalFieldCatalog::fields($chapter);
            $fields = array_diff($fields, $conditions);
            if ($chapter === 'bag-1') {
                $fields[] = 'coordinates_raw';
                $fields[] = 'map_source';
            }
            // Legacy flat payloads must keep every chapter when Bab 1 becomes nested.
            foreach ($fields as $field) {
                if (array_key_exists($field, $payload)) {
                    $payload[$chapter][$field] ??= $payload[$field];
                    unset($payload[$field]);
                }
            }
            if ($chapter === 'bag-1') {
                $payload[$chapter] = array_replace(array_fill_keys($fields, null), $payload[$chapter] ?? []);
            }
        }
        foreach ($conditions as $field) {
            $payload[$field] ??= null;
        }

        return $payload;
    }

    /** Empty input scaffolding is UI state, not proposal data or document identity. */
    private function payloadForStorage(KkprlProposal $proposal): array
    {
        $payload = $this->editingPayload($this->payload);
        $payload['bag-1'] = array_filter($payload['bag-1'] ?? [], fn ($value): bool => $value !== null);
        foreach (['includes_reclamation', 'land_relation', 'has_existing_permits'] as $field) {
            if (($payload[$field] ?? null) === null) {
                unset($payload[$field]);
            }
        }

        $stored = $proposal->payload ?? [];
        $chapters = ['bag-1', 'bag-2', 'bag-3', 'bag-4', 'bag-5'];
        if ($stored !== [] && ! collect($chapters)->contains(fn ($chapter): bool => is_array($stored[$chapter] ?? null))) {
            // Preserve legacy storage shape: document identity may include its
            // original flat condition fields, even though the UI uses chapters.
            foreach ($chapters as $chapter) {
                $payload = array_replace($payload, $payload[$chapter] ?? []);
                unset($payload[$chapter]);
            }
        }

        return $payload;
    }

    private function syncAttachmentChapter(): void
    {
        $this->attachmentChapter = $this->relevantChapters[$this->currentStep - 1] ?? 'bag-1';
    }
}
