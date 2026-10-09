<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposalChat;
use App\Models\KkprlProposalReviewEvent;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use App\Services\KkprlProposalDraftService;
use App\Services\KkprlProposalSnapshotService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlInstitutionSchemaRepairTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    private const REPAIR = '2026_10_09_000001_repair_kkprl_proposal_institution_columns';

    public static function missingColumns(): array
    {
        return [
            'both missing' => [['institution_name', 'institution_name_encrypted']],
            'legacy missing' => [['institution_name']],
            'encrypted missing' => [['institution_name_encrypted']],
            'intact schema' => [[]],
        ];
    }

    #[DataProvider('missingColumns')]
    public function test_repair_preserves_existing_values_and_restores_encrypted_autosave(array $missing): void
    {
        Http::preventStrayRequests();
        $proposal = $this->ownedProposal([
            'institution_name' => 'Institusi fixture lama',
            'payload' => ['institution_name' => 'Institusi fixture lama', 'applicant_name' => 'Pemohon fixture'],
        ]);
        if ($missing !== []) {
            Schema::table('kkprl_proposals', fn ($table) => $table->dropColumn($missing));
        }
        $before = (array) DB::table('kkprl_proposals')->where('id', $proposal->id)->first();

        if (count($missing) === 2) {
            try {
                app(KkprlProposalDraftService::class)->save($proposal->fresh(), ['institution_name' => 'Institusi fixture baru'], 1);
                $this->fail('Autosave must fail before repairing the missing columns.');
            } catch (QueryException $exception) {
                $this->assertStringContainsString('institution_name', $exception->getMessage());
            }
        }

        $migration = require database_path('migrations/'.self::REPAIR.'.php');
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('kkprl_proposals', 'institution_name'));
        $this->assertTrue(Schema::hasColumn('kkprl_proposals', 'institution_name_encrypted'));
        $after = (array) DB::table('kkprl_proposals')->where('id', $proposal->id)->first();
        foreach ($before as $column => $value) {
            $this->assertSame($value, $after[$column], $column.' changed during repair.');
        }
        foreach ($missing as $column) {
            $this->assertNull($after[$column]);
        }

        app(KkprlProposalDraftService::class)->save($proposal->fresh(), [
            'institution_name' => 'Institusi fixture baru',
            'applicant_name' => 'Pemohon fixture',
        ], 1);
        $this->assertSame('Institusi fixture baru', $proposal->fresh()->institution_name);
        $raw = DB::table('kkprl_proposals')->where('id', $proposal->id)->first();
        $this->assertNull($raw->institution_name);
        $this->assertNotEmpty($raw->institution_name_encrypted);
        $this->assertNotSame('Institusi fixture baru', $raw->institution_name_encrypted);
        Http::assertNothingSent();
    }

    public function test_artisan_runs_new_repair_when_original_migration_is_already_recorded(): void
    {
        $this->assertDatabaseHas('migrations', [
            'migration' => '2026_08_24_000002_add_institution_protection_to_kkprl_proposals',
        ]);
        Schema::table('kkprl_proposals', fn ($table) => $table->dropColumn(['institution_name', 'institution_name_encrypted']));
        // Remove only the repair's ledger entry inside this isolated test database.
        DB::table('migrations')->where('migration', self::REPAIR)->delete();

        $this->artisan('migrate', [
            '--path' => [database_path('migrations/'.self::REPAIR.'.php')],
            '--realpath' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('migrations', ['migration' => self::REPAIR]);
        $this->assertTrue(Schema::hasColumn('kkprl_proposals', 'institution_name'));
        $this->assertTrue(Schema::hasColumn('kkprl_proposals', 'institution_name_encrypted'));
    }

    public function test_repair_keeps_owned_revision_history_private_artifacts_and_snapshot_integrity(): void
    {
        Http::preventStrayRequests();
        Storage::fake('kkprl_private');
        $payload = [];
        foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
            foreach (ProposalProgress::requiredFields($chapter) as $field) {
                $payload[$field] = KkprlPayloadFixture::value($field);
            }
        }
        $proposal = $this->ownedProposal(['payload' => $payload]);
        $attachment = app(KkprlProposalAttachmentService::class)->store($proposal, 'bag-1', UploadedFile::fake()->image('fixture.png'));
        $document = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1', ['pdf'])->first();
        KkprlProposalChat::create(['proposal_id' => $proposal->id, 'chapter' => 'bag-1', 'messages' => [['role' => 'user', 'content' => 'Riwayat fixture.']]]);
        KkprlProposalReviewEvent::create(['proposal_id' => $proposal->id, 'actor_type' => 'system', 'event_type' => 'fixture_review', 'reason' => 'Audit fixture.', 'before_payload' => $payload, 'after_payload' => $payload, 'metadata' => [], 'created_at' => now()]);
        $proposal->update(['status' => 'submitted']);
        $revision = $this->ownedProposal(['root_proposal_id' => $proposal->id, 'revision_of_id' => $proposal->id, 'ticket_number' => $proposal->ticket_number, 'revision_number' => 1, 'payload' => $payload]);
        Schema::table('kkprl_proposals', fn ($table) => $table->dropColumn(['institution_name', 'institution_name_encrypted']));
        $tables = ['kkprl_proposals', 'kkprl_proposal_chats', 'kkprl_proposal_attachments', 'kkprl_proposal_documents', 'kkprl_proposal_review_events'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }

        $migration = require database_path('migrations/'.self::REPAIR.'.php');
        $migration->up();
        foreach ($tables as $table) {
            $after = DB::table($table)->orderBy('id')->get()->map(function ($row) use ($table) {
                $data = (array) $row;
                if ($table === 'kkprl_proposals') {
                    unset($data['institution_name'], $data['institution_name_encrypted']);
                }

                return $data;
            })->all();
            $this->assertSame($before[$table], $after, $table.' changed during repair.');
        }
        $this->assertSame($this->applicant->id, $proposal->fresh()->applicant_id);
        $this->assertSame($this->applicant->id, $revision->fresh()->applicant_id);
        Storage::disk('kkprl_private')->assertExists($attachment->storage_path);
        Storage::disk('kkprl_private')->assertExists($document->storage_path);
        $snapshot = app(KkprlProposalSnapshotService::class)->capture($proposal->fresh(), 'bag-1');
        $this->assertSame($document->snapshot_hash, $snapshot->snapshotHash);
        $this->assertSame($document->attachment_manifest_hash, $snapshot->attachmentManifestHash);
        Http::assertNothingSent();
    }

    public function test_down_retains_existing_institution_data(): void
    {
        $proposal = $this->ownedProposal(['institution_name' => 'Institusi fixture tetap']);
        $before = (array) DB::table('kkprl_proposals')->where('id', $proposal->id)->first();
        $migration = require database_path('migrations/'.self::REPAIR.'.php');
        $migration->down();

        $this->assertSame($before, (array) DB::table('kkprl_proposals')->where('id', $proposal->id)->first());
        $this->assertSame('Institusi fixture tetap', $proposal->fresh()->institution_name);
    }
}
