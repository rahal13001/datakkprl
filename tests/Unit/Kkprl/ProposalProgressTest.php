<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\ProposalProgress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\KkprlPayloadFixture;
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
            'shape_type' => 'point',
            'coordinates' => str_replace('(-6.2)', '(999)', KkprlPayloadFixture::value('coordinates')),
        ]);

        $this->assertSame('error', $progress->chapters['bag-1']['status']);
        $this->assertContains('fields.coordinates', $progress->errors['bag-1']);
    }

    #[Test]
    public function conditional_decisions_are_required_in_base_chapter(): void
    {
        $payload = array_fill_keys(ProposalProgress::requiredFields('bag-1'), 'Terisi');
        $payload['shape_type'] = KkprlPayloadFixture::value('shape_type');
        $payload['coordinates'] = KkprlPayloadFixture::value('coordinates');
        unset($payload['land_relation'], $payload['has_existing_permits']);

        $this->assertSame('incomplete', app(ProposalProgress::class)->evaluate($payload)->chapters['bag-1']['status']);
    }

    #[Test]
    #[DataProvider('invalidMapCoordinates')]
    public function invalid_map_coordinates_prevent_a_complete_chapter(mixed $raw, string $shape): void
    {
        $bag = array_fill_keys(ProposalProgress::requiredFields('bag-1'), 'Terisi');
        $bag['coordinates'] = KkprlPayloadFixture::value('coordinates');
        $bag['shape_type'] = $shape;
        $bag['coordinates_raw'] = $raw;

        $result = app(ProposalProgress::class)->evaluate(['bag-1' => $bag]);

        $this->assertSame('error', $result->chapters['bag-1']['status']);
        $this->assertContains('fields.coordinates', $result->errors['bag-1']);
    }

    public static function invalidMapCoordinates(): array
    {
        return [
            'latitude outside WGS84' => ['[{"lat_dd":"999","lng_dd":"106.8"}]', 'point'],
            'longitude outside WGS84' => ['[{"lat_dd":"-6.2","lng_dd":"181"}]', 'point'],
            'partial point' => ['[{"lat_dd":"-6.2","lng_dd":""}]', 'point'],
            'non numeric point' => ['[{"lat_dd":"bad","lng_dd":"106.8"}]', 'point'],
            'broken JSON' => ['{', 'point'],
            'JSON scalar' => ['true', 'point'],
            'JSON object' => ['{"lat_dd":"-6.2","lng_dd":"106.8"}', 'point'],
            'empty list' => ['[]', 'point'],
            'malformed row' => ['[null]', 'point'],
            'wrong storage type' => [true, 'point'],
            'line needs two points' => ['[{"lat_dd":"-6.2","lng_dd":"106.8"}]', 'line'],
            'polygon needs three points' => ['[{"lat_dd":"-6.2","lng_dd":"106.8"}]', 'polygon'],
        ];
    }

    #[Test]
    public function valid_map_coordinates_and_decimal_boundaries_are_accepted(): void
    {
        foreach (['point' => 1, 'line' => 2, 'polygon' => 3] as $shape => $count) {
            $payload = array_fill_keys(ProposalProgress::requiredFields('bag-1'), 'Terisi');
            $payload['shape_type'] = $shape;
            $payload['coordinates'] = KkprlPayloadFixture::value('coordinates');
            $payload['coordinates_raw'] = json_encode(array_slice([
                ['lat_dd' => '-90', 'lng_dd' => '-180'],
                ['lat_dd' => '90', 'lng_dd' => '180'],
                ['lat_dd' => '0', 'lng_dd' => '0'],
            ], 0, $count));

            $this->assertSame('complete', app(ProposalProgress::class)->evaluate($payload)->chapters['bag-1']['status']);
        }
    }

    #[Test]
    public function arbitrary_coordinate_text_and_unknown_shapes_are_rejected(): void
    {
        $payload = ['shape_type' => 'point', 'coordinates' => 'Bukan koordinat'];
        $this->assertContains('fields.coordinates', app(ProposalProgress::class)->evaluate($payload)->errors['bag-1']);
        $payload['coordinates'] = KkprlPayloadFixture::value('coordinates');
        $payload['shape_type'] = 'unknown';
        $this->assertContains('fields.shape_type', app(ProposalProgress::class)->evaluate($payload)->errors['bag-1']);
    }

    #[Test]
    public function optional_permit_details_and_documents_do_not_block_chapter_five(): void
    {
        $payload = ['has_existing_permits' => true, 'bag-5' => ['permit_type' => 'Izin usaha', 'permit_year' => '2025', 'permit_status' => 'Berlaku']];
        $state = app(ProposalProgress::class)->evaluate($payload)->chapters['bag-5'];
        $this->assertSame('complete', $state['status']);
        $this->assertSame(3, $state['total']);
        unset($payload['bag-5']['permit_type']);
        $this->assertSame('incomplete', app(ProposalProgress::class)->evaluate($payload)->chapters['bag-5']['status']);
    }

    #[Test]
    public function adjacent_land_without_permits_counts_only_required_land_information(): void
    {
        $payload = ['land_relation' => 'adjacent', 'has_existing_permits' => false, 'bag-5' => [
            'land_status' => 'Berhimpitan dengan lahan yang dikuasai pemohon.', 'land_evidence_type' => 'Sertifikat', 'land_acquisition_year' => '2024',
        ]];
        $state = app(ProposalProgress::class)->evaluate($payload)->chapters['bag-5'];
        $this->assertSame('complete', $state['status']);
        $this->assertSame(3, $state['total']);
    }
}
