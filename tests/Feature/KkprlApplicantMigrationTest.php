<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposalChat;
use App\Models\KkprlProposalReviewEvent;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlApplicantMigrationTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    public function test_additive_migration_preserves_legacy_proposal_chat_attachment_document_and_revision(): void
    {
        Storage::fake('kkprl_private');
        $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '081234567890']);
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = KkprlPayloadFixture::value($field);
            }
        }
        $proposal->update(['payload' => $payload]);
        $attachment = app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-1', UploadedFile::fake()->image('legacy.png'));
        $document = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf'])->first();
        KkprlProposalChat::create(['proposal_id' => $proposal->id, 'chapter' => 'bag-3', 'messages' => [['role' => 'user', 'content' => 'Riwayat lama.']]]);
        KkprlProposalReviewEvent::create(['proposal_id' => $proposal->id, 'actor_type' => 'system', 'event_type' => 'legacy_review', 'reason' => 'Riwayat peninjauan lama.', 'before_payload' => $payload, 'after_payload' => $payload, 'metadata' => ['source' => 'fixture'], 'created_at' => now()]);
        $proposal->update(['status' => 'submitted']);
        $this->ownedProposal(['root_proposal_id' => $proposal->id, 'revision_of_id' => $proposal->id, 'ticket_number' => $proposal->ticket_number, 'revision_number' => 1]);

        // Build a pre-account schema solely inside this test's SQLite database.
        $migration = require database_path('migrations/2026_10_07_000001_create_kkprl_applicant_accounts.php');
        $migration->down();
        $tables = ['kkprl_proposals', 'kkprl_proposal_chats', 'kkprl_proposal_attachments', 'kkprl_proposal_documents', 'kkprl_proposal_review_events'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }
        $migration->up();
        foreach ($tables as $table) {
            $after = DB::table($table)->orderBy('id')->get()->map(function ($row) {
                $data = (array) $row;
                unset($data['applicant_id']);

                return $data;
            })->all();
            $this->assertSame($before[$table], $after, $table.' changed legacy rows.');
        }
        $this->assertSame(2, DB::table('kkprl_proposals')->whereNull('applicant_id')->count());
        $this->assertDatabaseCount('kkprl_applicants', 0);
        $this->assertDatabaseCount('kkprl_applicant_events', 0);
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
        Storage::disk('kkprl_private')->assertExists($document->storage_path);
        $snapshot = app(KkprlProposalSnapshotService::class)->capture($proposal->fresh(), 'bag-1');
        $this->assertSame($document->snapshot_hash, $snapshot->snapshotHash);
    }
}
