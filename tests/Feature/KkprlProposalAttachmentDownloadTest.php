<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KkprlProposalAttachmentDownloadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function applicant_can_download_own_private_attachment(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $attachment = app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-1', UploadedFile::fake()->image('map.png'));

        $this->get(route('kkprl.proposal.attachment.download', $attachment))->assertOk();
    }

    #[Test]
    public function applicant_session_cannot_download_another_proposals_attachment(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $first = $access->startDraft(['phone' => '0812-1111-1111']);
        $attachment = app(KkprlProposalAttachmentService::class)->store($first, 'bag-1', UploadedFile::fake()->image('map.png'));
        $second = $access->startDraft(['phone' => '0812-2222-2222']);
        $access->grantAccess($second);

        $this->get(route('kkprl.proposal.attachment.download', $attachment))->assertNotFound();
    }

    #[Test]
    public function staff_download_requires_attachment_permission(): void
    {
        Storage::fake('kkprl_private');
        $access = app(KkprlProposalAccessService::class);
        $proposal = $access->startDraft(['phone' => '0812-3456-7890']);
        $attachment = app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-1', UploadedFile::fake()->image('map.png'));
        $access->forget();
        $staff = User::factory()->create();

        $staff->givePermissionTo(Permission::findOrCreate('Review:KkprlProposal', 'web'));
        $this->actingAs($staff)->get(route('kkprl.proposal.attachment.download', $attachment))->assertNotFound();
        $staff->givePermissionTo(Permission::findOrCreate('Download:KkprlProposalAttachment', 'web'));
        $this->actingAs($staff)->get(route('kkprl.proposal.attachment.download', $attachment))->assertOk();
    }
}
