<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalLocked;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KkprlProposalDraftTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function autosave_updates_the_same_draft_idempotently(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft([
            'phone' => '0812-3456-7890',
        ]);
        $service = app(KkprlProposalDraftService::class);

        $first = $service->save($proposal, ['bag-1' => ['province' => 'Bali']], 2);
        $second = $service->save($proposal->fresh(), ['bag-1' => ['province' => 'Bali']], 2);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, $proposal->newQuery()->whereKey($proposal->id)->count());
        $this->assertSame(['bag-1' => ['province' => 'Bali']], $second->payload);
        $this->assertSame(2, $second->current_step);
        $this->assertGreaterThan(0, $second->progress_percent);
        $this->assertNotNull($second->last_saved_at);
    }

    #[Test]
    public function submitted_proposal_cannot_be_autosaved(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft([
            'phone' => '0812-3456-7890',
        ]);
        $proposal->update(['status' => 'submitted']);

        $this->expectException(ProposalLocked::class);
        app(KkprlProposalDraftService::class)->save($proposal, ['bag-1' => []], 1);
    }

    #[Test]
    public function stale_autosave_instance_cannot_write_after_proposal_is_locked(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft([
            'phone' => '0812-3456-7890',
        ]);
        $stale = $proposal->fresh();
        $proposal->update(['status' => 'submitted']);

        $this->expectException(ProposalLocked::class);
        app(KkprlProposalDraftService::class)->save($stale, ['bag-1' => ['province' => 'Tidak boleh']], 1);
    }

    #[Test]
    public function autosave_drops_unknown_fields_but_keeps_known_inactive_conditional_values(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft([
            'phone' => '0812-3456-7890',
        ]);

        $saved = app(KkprlProposalDraftService::class)->save($proposal, [
            'includes_reclamation' => false,
            'bag-1' => [
                'province' => 'Bali',
                'secret' => 'must not persist',
            ],
            'bag-4' => [
                'material_method' => 'Tetap tersimpan sebagai histori draft',
                'secret' => 'must not persist',
            ],
            'unknown' => 'must not persist',
        ], 1);

        $this->assertSame('Bali', $saved->payload['bag-1']['province']);
        $this->assertArrayNotHasKey('secret', $saved->payload['bag-1']);
        $this->assertSame('Tetap tersimpan sebagai histori draft', $saved->payload['bag-4']['material_method']);
        $this->assertArrayNotHasKey('secret', $saved->payload['bag-4']);
        $this->assertArrayNotHasKey('unknown', $saved->payload);
    }
}
