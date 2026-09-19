<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\ProposalProgress;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProposalProgressTest extends TestCase
{
    #[Test]
    public function base_chapters_are_incomplete_when_required_fields_are_empty(): void
    {
        $progress = app(ProposalProgress::class)->evaluate([]);

        $this->assertSame(['bag-1', 'bag-2', 'bag-3'], $progress->relevantChapters);
        $this->assertSame('incomplete', $progress->chapters['bag-1']['status']);
        $this->assertSame(0, $progress->percent);
    }

    #[Test]
    public function conditional_chapters_only_count_when_selected(): void
    {
        $progress = app(ProposalProgress::class)->evaluate([
            'includes_reclamation' => true,
            'land_relation' => 'adjacent',
            'has_existing_permits' => true,
        ]);

        $this->assertSame(
            ['bag-1', 'bag-2', 'bag-3', 'bag-4', 'bag-5'],
            $progress->relevantChapters,
        );
        $this->assertSame('incomplete', $progress->chapters['bag-4']['status']);
        $this->assertSame('incomplete', $progress->chapters['bag-5']['status']);
    }

    #[Test]
    public function explicit_not_applicable_values_count_as_completed(): void
    {
        $payload = array_fill_keys(ProposalProgress::requiredFields('bag-2'), 'Tidak berlaku');

        $progress = app(ProposalProgress::class)->evaluate($payload);

        $this->assertSame('complete', $progress->chapters['bag-2']['status']);
        $this->assertGreaterThan(0, $progress->percent);
    }

    #[Test]
    public function false_select_values_do_not_activate_conditional_chapters(): void
    {
        $progress = app(ProposalProgress::class)->evaluate([
            'includes_reclamation' => '0',
            'land_relation' => 'not_adjacent',
            'has_existing_permits' => '0',
        ]);

        $this->assertSame(['bag-1', 'bag-2', 'bag-3'], $progress->relevantChapters);
    }

    #[Test]
    public function incomplete_schedule_row_keeps_reclamation_chapter_incomplete(): void
    {
        $payload = ['includes_reclamation' => true, 'bag-4' => []];
        foreach (ProposalProgress::requiredFields('bag-4') as $field) {
            $payload['bag-4'][$field] = $field === 'reclamation_schedule_rows'
                ? [['activity' => 'Pengerukan', 'start_date' => '2026-01-01', 'end_date' => '', 'notes' => 'Catatan']]
                : 'Terisi';
        }

        $this->assertSame('incomplete', app(ProposalProgress::class)->evaluate($payload)->chapters['bag-4']['status']);

        $payload['bag-4']['reclamation_schedule_rows'][0]['end_date'] = '2026-02-01';
        $this->assertSame('complete', app(ProposalProgress::class)->evaluate($payload)->chapters['bag-4']['status']);
    }

    #[Test]
    public function string_true_permit_selection_activates_permit_fields(): void
    {
        $progress = app(ProposalProgress::class)->evaluate([
            'has_existing_permits' => '1',
        ]);

        $this->assertContains('bag-5', $progress->relevantChapters);
        $this->assertSame('incomplete', $progress->chapters['bag-5']['status']);
    }

    #[Test]
    public function invalid_coordinate_is_reported_as_error_not_incomplete(): void
    {
        $progress = app(ProposalProgress::class)->evaluate([
            'latitude' => '999',
            'longitude' => '106.8',
        ]);

        $this->assertSame('error', $progress->chapters['bag-1']['status']);
        $this->assertContains('fields.latitude', $progress->errors['bag-1']);
    }

    #[Test]
    public function conditional_decisions_are_required_in_base_chapter(): void
    {
        $payload = array_fill_keys(ProposalProgress::requiredFields('bag-1'), 'Terisi');
        $payload['latitude'] = '-6.2';
        $payload['longitude'] = '106.8';
        unset($payload['land_relation'], $payload['has_existing_permits']);

        $this->assertSame('incomplete', app(ProposalProgress::class)->evaluate($payload)->chapters['bag-1']['status']);
    }
}
