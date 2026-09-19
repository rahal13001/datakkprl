<?php

namespace Tests\Feature;

use App\Domain\Kkprl\AttachmentQuotaViolation;
use App\Domain\Kkprl\InvalidAttachmentFile;
use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposalAttachment;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KkprlProposalAttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('kkprl_private');
    }

    #[Test]
    public function accepted_image_is_private_and_keeps_inline_anchor_metadata(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $file = UploadedFile::fake()->image('site-plan.jpg', 40, 20);

        $attachment = app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            $file,
            [
                'placement' => 'inline',
                'anchor_key' => 'site_plan_description',
                'caption' => 'Site plan utama',
            ],
        );

        $this->assertSame('inline', $attachment->placement);
        $this->assertSame('site_plan_description', $attachment->anchor_key);
        $this->assertSame('site_plan_description', $attachment->field);
        $this->assertSame('image/jpeg', $attachment->mime_type);
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
        $this->assertStringNotContainsString('/public/', $attachment->storage_path);
    }

    #[Test]
    public function attachment_cannot_be_persisted_on_a_non_private_disk(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);

        $this->expectException(InvalidAttachmentFile::class);
        KkprlProposalAttachment::create([
            'proposal_id' => $proposal->id,
            'chapter' => 'bag-1',
            'attachment_role' => 'supporting',
            'placement' => 'appendix',
            'original_name' => 'public.png',
            'storage_disk' => 'public',
            'storage_path' => 'kkprl/public.png',
            'mime_type' => 'image/png',
            'size' => 10,
        ]);
    }

    #[Test]
    public function invalid_signature_is_rejected_even_when_extension_looks_valid(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $file = UploadedFile::fake()->createWithContent('not-an-image.jpg', 'plain text');

        $this->expectException(InvalidAttachmentFile::class);
        app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-1', $file);
    }

    #[Test]
    public function pdf_png_and_webp_signatures_are_accepted(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $service = app(KkprlProposalAttachmentService::class);

        $pdf = UploadedFile::fake()->createWithContent('support.pdf', "%PDF-1.7\n1 0 obj\n<<>>\nendobj\n");
        $png = UploadedFile::fake()->image('map.png', 20, 20);
        $webp = UploadedFile::fake()->image('photo.webp', 20, 20);

        $this->assertSame('application/pdf', $service->store($proposal, 'bag-1', $pdf)->mime_type);
        $this->assertSame('image/png', $service->store($proposal, 'bag-1', $png)->mime_type);
        $this->assertSame('image/webp', $service->store($proposal, 'bag-1', $webp)->mime_type);
    }

    #[Test]
    public function eleventh_active_attachment_is_rejected_per_chapter(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $service = app(KkprlProposalAttachmentService::class);

        for ($index = 0; $index < 10; $index++) {
            $service->store($proposal, 'bag-1', UploadedFile::fake()->image("file-{$index}.png"));
        }

        $this->expectException(AttachmentQuotaViolation::class);
        $service->store($proposal, 'bag-1', UploadedFile::fake()->image('file-11.png'));
    }

    #[Test]
    public function multi_file_upload_is_atomic_when_count_quota_would_be_exceeded(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $service = app(KkprlProposalAttachmentService::class);

        for ($index = 0; $index < 9; $index++) {
            $service->store($proposal, 'bag-1', UploadedFile::fake()->image("file-{$index}.png"));
        }

        $this->expectException(AttachmentQuotaViolation::class);
        try {
            $service->storeMany($proposal, 'bag-1', [
                UploadedFile::fake()->image('batch-one.png'),
                UploadedFile::fake()->image('batch-two.png'),
            ]);
        } finally {
            $this->assertSame(9, $proposal->attachments()->count());
        }
    }

    #[Test]
    public function inactive_chapter_cannot_receive_an_attachment(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);

        $this->expectException(InvalidAttachmentFile::class);
        app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-4', UploadedFile::fake()->image('map.png'));
    }

    #[Test]
    public function stale_attachment_upload_cannot_target_a_chapter_disabled_after_request_start(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $proposal->update(['payload' => ['includes_reclamation' => true]]);
        $staleProposal = $proposal->fresh();

        $proposal->update(['payload' => ['includes_reclamation' => false]]);

        $this->expectException(InvalidAttachmentFile::class);
        app(KkprlProposalAttachmentService::class)->store(
            $staleProposal,
            'bag-4',
            UploadedFile::fake()->image('stale-map.png'),
        );
    }

    #[Test]
    public function stale_attachment_replace_cannot_target_a_chapter_disabled_after_request_start(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $proposal->update(['payload' => ['includes_reclamation' => true]]);
        $service = app(KkprlProposalAttachmentService::class);
        $attachment = $service->store($proposal, 'bag-4', UploadedFile::fake()->image('old-map.png'));
        $staleProposal = $proposal->fresh();

        $proposal->update(['payload' => ['includes_reclamation' => false]]);

        $this->expectException(InvalidAttachmentFile::class);
        $service->replace($staleProposal, $attachment, UploadedFile::fake()->image('stale-map.png'));
    }

    #[Test]
    public function deleting_attachment_frees_active_quota(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $service = app(KkprlProposalAttachmentService::class);
        $items = [];

        for ($index = 0; $index < 10; $index++) {
            $items[] = $service->store($proposal, 'bag-1', UploadedFile::fake()->image("file-{$index}.png"));
        }

        $service->delete($proposal, $items[0]);
        $replacement = $service->store($proposal, 'bag-1', UploadedFile::fake()->image('replacement.png'));

        $this->assertNotNull($replacement);
        $this->assertSame(10, $proposal->attachments()->count());
        $this->assertSame(1, $proposal->attachments()->whereKey($replacement->id)->count());
    }

    #[Test]
    public function replacing_attachment_recalculates_size_and_metadata(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $service = app(KkprlProposalAttachmentService::class);
        $original = $service->store($proposal, 'bag-1', UploadedFile::fake()->image('old.png'));

        $replaced = $service->replace($proposal, $original, UploadedFile::fake()->image('new.png'), [
            'placement' => 'appendix',
            'caption' => 'Versi baru',
        ]);

        $this->assertSame($original->id, $replaced->id);
        $this->assertSame('new.png', $replaced->original_name);
        $this->assertSame('Versi baru', $replaced->caption);
        $this->assertSame(1, $proposal->attachments()->count());
    }

    #[Test]
    public function replacement_file_is_removed_when_attachment_update_fails(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $service = app(KkprlProposalAttachmentService::class);
        $original = $service->store($proposal, 'bag-1', UploadedFile::fake()->image('old.png'));
        $oldPath = $original->storage_path;
        $eventName = 'eloquent.saving: '.KkprlProposalAttachment::class;
        $forceFailure = true;

        Event::listen($eventName, function (KkprlProposalAttachment $attachment) use (&$forceFailure): void {
            if ($forceFailure && $attachment->isDirty('caption') && $attachment->caption === 'FORCE_FAILURE') {
                throw new \RuntimeException('Synthetic attachment update failure.');
            }
        });

        $thrown = null;
        try {
            $service->replace($proposal, $original, UploadedFile::fake()->image('new.png'), [
                'caption' => 'FORCE_FAILURE',
            ]);
        } catch (\Throwable $exception) {
            $thrown = $exception;
        } finally {
            $forceFailure = false;
        }

        $this->assertInstanceOf(\RuntimeException::class, $thrown);
        Storage::disk('kkprl_private')->assertExists($oldPath);
        $this->assertSame($oldPath, $original->fresh()->storage_path);
        $this->assertCount(1, Storage::disk('kkprl_private')->allFiles());
    }

    #[Test]
    public function client_filename_is_sanitized_for_display(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $attachment = app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->image('../unsafe name.png'),
        );

        $this->assertSame('unsafe_name.png', $attachment->original_name);
        $this->assertStringNotContainsString('..', $attachment->original_name);
    }

    #[Test]
    public function unresolved_inline_anchor_is_rejected_at_upload(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = $field === 'latitude' ? '-6.2' : ($field === 'longitude' ? '106.8' : 'Terisi');
            }
        }
        $proposal->update(['payload' => $payload]);
        $this->expectException(InvalidAttachmentFile::class);
        app(KkprlProposalAttachmentService::class)->store(
            $proposal,
            'bag-1',
            UploadedFile::fake()->image('site-plan.png'),
            ['placement' => 'inline', 'anchor_key' => 'unknown-text-anchor'],
        );
    }
}
