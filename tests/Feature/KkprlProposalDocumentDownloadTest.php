<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalProgress;
use App\Models\User;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KkprlProposalDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function applicant_can_download_document_from_current_session(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $proposal->update(['payload' => $this->completePayload()]);
        $document = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf'])->first();

        $response = $this->get(route('kkprl.proposal.document.download', $document));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    #[Test]
    public function session_cannot_download_another_proposals_document(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $first = $access->startDraft(['phone' => '0812-1111-1111']);
        $first->update(['payload' => $this->completePayload()]);
        $document = app(KkprlProposalDocumentService::class)->generateChapterFormats($first->fresh(), 'bag-1', ['pdf'])->first();
        $second = $access->startDraft(['phone' => '0812-2222-2222']);
        $access->grantAccess($second);

        $this->get(route('kkprl.proposal.document.download', $document))->assertNotFound();
    }

    #[Test]
    public function staff_download_requires_and_accepts_document_permission(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $proposal->update(['payload' => $this->completePayload()]);
        $document = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf'])->first();
        $access->forget();
        $staff = User::factory()->create();

        $staff->givePermissionTo(Permission::findOrCreate('Review:KkprlProposal', 'web'));
        $this->actingAs($staff)->get(route('kkprl.proposal.document.download', $document))->assertNotFound();
        $staff->givePermissionTo(Permission::findOrCreate('Download:KkprlProposalDocument', 'web'));
        $this->actingAs($staff)->get(route('kkprl.proposal.document.download', $document))->assertOk();
    }

    #[Test]
    public function draft_cannot_download_a_document_from_an_old_conditional_snapshot(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $payload = $this->completePayload();
        $payload['includes_reclamation'] = true;
        $payload['bag-4'] = array_fill_keys(ProposalProgress::requiredFields('bag-4'), 'Terisi');
        $payload['bag-4']['reclamation_schedule_rows'] = [[
            'activity' => 'Pengerukan',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-01',
            'notes' => 'Tahap awal',
        ]];
        $proposal->update(['payload' => $payload]);
        $document = app(KkprlProposalDocumentService::class)
            ->generateChapterFormats($proposal->fresh(), 'bag-4', ['pdf'])
            ->first();

        $payload['includes_reclamation'] = false;
        $proposal->update(['payload' => $payload]);

        $this->get(route('kkprl.proposal.document.download', $document))->assertNotFound();
    }

    /** @return array<string, string> */
    private function completePayload(): array
    {
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }

        return $payload;
    }
}
