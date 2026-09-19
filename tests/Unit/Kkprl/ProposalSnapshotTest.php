<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\ProposalProgress;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProposalSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function snapshot_is_deterministic_and_manifest_is_chapter_scoped(): void
    {
        Storage::fake('local');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        $proposal->update(['payload' => $payload]);
        $attachments = app(KkprlProposalAttachmentService::class);
        $attachments->store($proposal, 'bag-1', UploadedFile::fake()->image('map.png'));
        $attachments->store($proposal, 'bag-2', UploadedFile::fake()->image('other.png'));

        $service = app(KkprlProposalSnapshotService::class);
        $first = $service->capture($proposal->fresh(), 'bag-1');
        $second = $service->capture($proposal->fresh(), 'bag-1');

        $this->assertSame($first->snapshotHash, $second->snapshotHash);
        $this->assertSame($first->attachmentManifestHash, $second->attachmentManifestHash);
        $this->assertSame($proposal->created_at->year, $first->payload['proposal_year']);
        $this->assertCount(1, $first->attachments);
        $this->assertSame('bag-1', $first->attachments[0]['chapter']);
    }

    #[Test]
    public function incomplete_chapter_cannot_be_snapshotted(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);

        $this->expectException(ValidationException::class);
        app(KkprlProposalSnapshotService::class)->capture($proposal, 'bag-1');
    }

    #[Test]
    public function snapshot_excludes_inactive_conditional_payload(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        $payload['bag-4'] = ['internal_only' => 'do not render'];
        $proposal->update(['payload' => $payload]);

        $snapshot = app(KkprlProposalSnapshotService::class)->capture($proposal->fresh(), 'bag-1');

        $this->assertArrayNotHasKey('bag-4', $snapshot->payload['payload']);
        $this->assertArrayHasKey('bag-1', $snapshot->payload['payload']);
    }

    #[Test]
    public function snapshot_filters_stale_nested_bag_five_fields(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        $payload['land_relation'] = 'adjacent';
        $payload['has_existing_permits'] = '0';
        foreach (ProposalProgress::requiredFields('bag-5-land') as $field) {
            $payload['bag-5'][$field] = 'Terisi';
        }
        $payload['bag-5']['permit_number'] = 'STALE-DO-NOT-RENDER';
        $proposal->update(['payload' => $payload]);

        $snapshot = app(KkprlProposalSnapshotService::class)->capture($proposal->fresh(), 'bag-5');

        $this->assertArrayNotHasKey('permit_number', $snapshot->payload['payload']['bag-5']);
        $this->assertArrayHasKey('land_status', $snapshot->payload['payload']['bag-5']);
    }
}
