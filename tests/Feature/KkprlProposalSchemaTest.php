<?php

namespace Tests\Feature;

use App\Models\KkprlProposal;
use App\Services\DataHashService;
use App\Services\KkprlProposalDraftService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KkprlProposalSchemaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function proposal_generates_ticket_and_protects_phone_and_payload(): void
    {
        $proposal = KkprlProposal::create([
            'applicant_name' => 'Pemohon Uji',
            'phone' => '+62 812-3456-7890',
            'payload' => ['includes_reclamation' => false],
            'province' => 'Papua Selatan',
            'regency' => 'Merauke',
        ]);

        $raw = DB::table('kkprl_proposals')->where('id', $proposal->id)->first();

        $this->assertNotEmpty($proposal->ticket_number);
        $this->assertSame($proposal->id, $proposal->root_proposal_id);
        $this->assertNull($raw->phone);
        $this->assertNull($raw->applicant_name);
        $this->assertNull($raw->payload);
        $this->assertNotEmpty($raw->phone_encrypted);
        $this->assertNotEmpty($raw->payload_encrypted);
        $this->assertSame(
            app(DataHashService::class)->phone('+6281234567890'),
            $raw->phone_hash,
        );
        $this->assertSame('draft', $proposal->status);
    }

    #[Test]
    public function revision_keeps_root_ticket_and_is_connected_to_original(): void
    {
        $original = KkprlProposal::create(['phone' => '081234567890']);
        $revision = KkprlProposal::create([
            'root_proposal_id' => $original->root_proposal_id,
            'revision_of_id' => $original->id,
            'revision_number' => 1,
            'revision_label' => 'rev-1',
            'ticket_number' => $original->ticket_number,
            'phone' => '081234567890',
        ]);

        $this->assertSame($original->ticket_number, $revision->ticket_number);
        $this->assertSame($original->root_proposal_id, $revision->root_proposal_id);
        $this->assertTrue($revision->revisionOf->is($original));
    }

    #[Test]
    public function draft_syncs_safe_review_columns_from_bag_one_payload(): void
    {
        $proposal = KkprlProposal::create(['phone' => '081234567890']);

        app(KkprlProposalDraftService::class)->save($proposal, [
            'bag-1' => [
                'applicant_name' => 'Pemohon Uji',
                'institution_name' => 'PT Samudra Uji',
                'province' => 'Papua Selatan',
                'regency' => 'Merauke',
                'main_activity' => 'Keramba jaring apung',
            ],
        ], 1);

        $fresh = $proposal->fresh();
        $this->assertSame('Pemohon Uji', $fresh->applicant_name);
        $this->assertSame('PT Samudra Uji', $fresh->institution_name);
        $this->assertSame('Papua Selatan', $fresh->province);
        $this->assertSame('Merauke', $fresh->regency);
        $this->assertSame('Keramba jaring apung', $fresh->activity_type);
        $this->assertNull(DB::table('kkprl_proposals')->where('id', $fresh->id)->value('applicant_name'));
        $this->assertNull(DB::table('kkprl_proposals')->where('id', $fresh->id)->value('institution_name'));
    }

    #[Test]
    public function serialized_proposal_does_not_expose_protection_columns(): void
    {
        $proposal = KkprlProposal::create([
            'phone' => '081234567890',
            'email' => 'pemohon@example.test',
            'applicant_name' => 'Pemohon Uji',
            'institution_name' => 'PT Samudra Uji',
            'payload' => ['bag-1' => ['identity_number' => 'secret']],
        ]);

        $serialized = $proposal->toArray();

        $this->assertArrayNotHasKey('phone_encrypted', $serialized);
        $this->assertArrayNotHasKey('phone_hash', $serialized);
        $this->assertArrayNotHasKey('payload_encrypted', $serialized);
        $this->assertSame('Pemohon Uji', $serialized['applicant_name']);
    }

    #[Test]
    public function a_ticket_can_be_shared_by_revisions_but_not_by_two_root_proposals(): void
    {
        $original = KkprlProposal::create(['phone' => '081234567890']);

        KkprlProposal::create([
            'ticket_number' => $original->ticket_number,
            'revision_number' => 1,
            'root_proposal_id' => $original->id,
            'revision_of_id' => $original->id,
            'revision_label' => 'rev-1',
            'phone' => '081234567890',
        ]);

        $this->expectException(QueryException::class);

        KkprlProposal::create([
            'ticket_number' => $original->ticket_number,
            'phone' => '081234567890',
        ]);
    }

    #[Test]
    public function attachment_stores_chapter_placement_and_private_path(): void
    {
        $proposal = KkprlProposal::create(['phone' => '081234567890']);

        $attachment = $proposal->attachments()->create([
            'chapter' => 'bag-1',
            'field' => 'site_plan',
            'attachment_role' => 'site_plan',
            'placement' => 'inline',
            'anchor_key' => 'bag-1.site_plan_description',
            'original_name' => 'site-plan.png',
            'storage_disk' => 'kkprl_private',
            'storage_path' => 'kkprl/proposals/1/site-plan.png',
            'mime_type' => 'image/png',
            'size' => 2048,
        ]);

        $this->assertTrue($proposal->attachments()->whereKey($attachment->id)->exists());
        $this->assertSame('inline', $attachment->placement);
        $this->assertSame('kkprl_private', $attachment->storage_disk);
    }
}
