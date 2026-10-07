<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalFieldCatalog;
use App\Domain\Kkprl\ProposalProgress;
use App\Livewire\KkprlProposalWizard;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\KkprlApplicantFixture;
use Tests\Support\KkprlPayloadFixture;
use Tests\TestCase;

class KkprlChapterOneInputTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    public function test_autosave_echo_keeps_input_key_order_and_filters_foreign_keys(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft();
        $input = ['bag-1' => ['applicant_name' => 'Pemohon sintetis'], 'land_relation' => 'not_adjacent', 'foreign_key' => 'discard'];
        $component = Livewire::test(KkprlProposalWizard::class)->set('payload', $input);

        $this->assertSame(['bag-1', 'land_relation'], array_slice(array_keys($component->get('payload')), 0, 2));
        $this->assertArrayNotHasKey('foreign_key', $component->get('payload'));
        $this->assertArrayNotHasKey('foreign_key', $proposal->fresh()->payload);
        $this->assertSame('Pemohon sintetis', $proposal->fresh()->payload['bag-1']['applicant_name']);
    }

    public function test_empty_form_keeps_the_same_object_keys_on_first_autosave_without_storing_null_scaffolding(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft();
        $component = Livewire::test(KkprlProposalWizard::class);
        $keys = array_keys($component->get('payload'));
        $fields = array_keys($component->get('payload')['bag-1']);
        $component->set('payload.bag-1.applicant_name', 'Pemohon sintetis');

        $this->assertSame($keys, array_keys($component->get('payload')));
        $this->assertSame($fields, array_keys($component->get('payload')['bag-1']));
        $this->assertSame(['bag-1' => ['applicant_name' => 'Pemohon sintetis']], $proposal->fresh()->payload);
    }

    public function test_legacy_flat_payload_keeps_other_chapters_when_editing_bab_one(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft();
        $proposal->update([
            'applicant_name' => 'Lama',
            'institution_name' => 'Instansi lama',
            'province' => 'Provinsi lama',
            'payload' => ['applicant_name' => 'Lama', 'institution_name' => 'Instansi lama', 'province' => 'Provinsi lama', 'marine_space_use_narrative' => 'Narasi Bab 2 lama', 'tide' => 'Narasi pasut lama'],
        ]);
        Livewire::test(KkprlProposalWizard::class)
            ->assertSet('payload.bag-1.applicant_name', 'Lama')
            ->set('payload.bag-1.applicant_name', 'Baru')
            ->assertSet('payload.bag-2.marine_space_use_narrative', 'Narasi Bab 2 lama');

        $this->assertSame('Narasi Bab 2 lama', $proposal->fresh()->payload['marine_space_use_narrative']);
        $this->assertSame('Narasi pasut lama', $proposal->fresh()->payload['tide']);
        $this->assertSame('Baru', $proposal->fresh()->applicant_name);
        $this->assertSame('Instansi lama', $proposal->fresh()->institution_name);
        $this->assertSame('Provinsi lama', $proposal->fresh()->province);
    }

    public function test_missing_report_identifies_the_depth_unit_without_losing_false_or_zero(): void
    {
        $bag = $this->completeBag();
        unset($bag['depth_unit']);
        $bag['workforce_male'] = 0;
        $bag['workforce_female'] = '0';
        $result = app(ProposalProgress::class)->evaluate(['bag-1' => $bag]);

        $this->assertSame(['depth_unit'], $result->chapters['bag-1']['missing']);
        $this->assertSame('incomplete', $result->chapters['bag-1']['status']);
        $bag['depth_unit'] = 'm; datum tidak diketahui';
        $result = app(ProposalProgress::class)->evaluate(['bag-1' => $bag]);
        $this->assertSame('complete', $result->chapters['bag-1']['status']);
        $this->assertSame([], $result->chapters['bag-1']['missing']);
        $this->assertLessThan(100, $result->percent);
    }

    public function test_empty_rich_text_does_not_complete_site_plan(): void
    {
        $bag = $this->completeBag();
        $bag['site_plan_description'] = '<p><strong>&nbsp; </strong></p>';
        $result = app(ProposalProgress::class)->evaluate(['bag-1' => $bag]);

        $this->assertSame('incomplete', $result->chapters['bag-1']['status']);
        $this->assertContains('site_plan_description', $result->chapters['bag-1']['missing']);
    }

    public function test_form_exposes_depth_unit_and_explains_missing_fields_without_editor_seed_text(): void
    {
        app(KkprlProposalAccessService::class)->startDraft();
        Livewire::test(KkprlProposalWizard::class)
            ->assertSee('Satuan kedalaman/datum')
            ->assertSee('wire:model.live.blur="payload.bag-1.depth_unit"', false)
            ->assertSee('Kelengkapan Bab 1')
            ->assertSee('Belum diisi')
            ->assertDontSee('Mulai mengetik deskripsi Anda di sini...')
            ->assertDontSee('Gunakan editor modern');
    }

    public function test_saved_site_plan_survives_navigation_and_reload(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft();
        $narrative = '<p>Dermaga berada di sisi timur dan terhubung ke akses darat.</p>';
        Livewire::test(KkprlProposalWizard::class)
            ->set('payload.bag-1.site_plan_description', $narrative)
            ->call('next')->call('previous')
            ->assertSet('payload.bag-1.site_plan_description', $narrative);
        $this->assertSame($narrative, $proposal->fresh()->payload['bag-1']['site_plan_description']);
        Livewire::test(KkprlProposalWizard::class)->assertSet('payload.bag-1.site_plan_description', $narrative);
    }

    public function test_ui_scaffolding_and_flat_payload_conversion_do_not_change_existing_document_identity(): void
    {
        $proposal = app(KkprlProposalAccessService::class)->startDraft();
        $payload = $this->completeBag();
        $payload['includes_reclamation'] = '0';
        $payload['has_existing_permits'] = '0';
        $proposal->update(['payload' => $payload]);
        $snapshots = app(KkprlProposalSnapshotService::class);
        $before = $snapshots->capture($proposal->fresh(), 'bag-1');
        Livewire::test(KkprlProposalWizard::class)->call('saveDraft');
        $after = $snapshots->capture($proposal->fresh(), 'bag-1');

        $this->assertSame($before->snapshotHash, $after->snapshotHash);
        $this->assertSame($before->attachmentManifestHash, $after->attachmentManifestHash);
        $this->assertArrayNotHasKey('coordinates_raw', $proposal->fresh()->payload);
    }

    private function completeBag(): array
    {
        return array_combine(ProposalFieldCatalog::fields('bag-1'), array_map(
            fn (string $field) => KkprlPayloadFixture::value($field, match ($field) {
                'includes_reclamation', 'has_existing_permits' => false,
                'land_relation' => 'not_adjacent',
                default => 'Terisi',
            }), ProposalFieldCatalog::fields('bag-1'),
        ));
    }
}
