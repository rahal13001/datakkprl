<?php

namespace Tests\Feature;

use App\Filament\Layanankkprl\Resources\ProposalGuides\Pages\ManageProposalGuides;
use App\Models\LearningCategory;
use App\Models\LearningGroup;
use App\Models\LearningMaterial;
use App\Models\ProposalGuide;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProposalGuideCountersTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_reads_and_downloads_increment_separate_counters_and_survive_pdf_replacement(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('guides/original.pdf', '%PDF-1.4'.str_repeat('a', 100));
        Storage::disk('public')->put('guides/replacement.pdf', '%PDF-1.4'.str_repeat('b', 100));
        $guide = ProposalGuide::create(['title' => 'Pedoman', 'pdf_path' => 'guides/original.pdf', 'is_published' => true]);
        $this->assertSame(0, $guide->fresh()->view_count);
        $this->assertSame(0, $guide->fresh()->download_count);
        $guide->forceFill(['view_count' => 96, 'download_count' => 65])->save();

        $this->get(route('pedoman-kkprl'))->assertOk();
        $this->assertSame(96, $guide->fresh()->view_count);
        $this->assertSame(65, $guide->fresh()->download_count);
        $this->get(route('pedoman-kkprl.pdf', $guide))->assertOk();
        $this->assertSame(97, $guide->fresh()->view_count);
        $this->assertSame(65, $guide->fresh()->download_count);
        $this->get(route('pedoman-kkprl.pdf', $guide))->assertOk();
        $this->get(route('pedoman-kkprl.download', $guide))->assertDownload('pedoman.pdf');
        $this->assertSame(98, $guide->fresh()->view_count);
        $this->assertSame(66, $guide->fresh()->download_count);

        $guide->update(['title' => 'Pedoman Baru', 'pdf_path' => 'guides/replacement.pdf']);
        $this->assertSame(98, $guide->fresh()->view_count);
        $this->assertSame(66, $guide->fresh()->download_count);
        $this->get(route('pedoman-kkprl.pdf', $guide))->assertOk();
        $this->assertSame(99, $guide->fresh()->view_count);
    }

    public function test_head_and_range_requests_do_not_inflate_document_counters(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('guides/guide.pdf', '%PDF-1.4'.str_repeat('a', 100));
        $guide = ProposalGuide::create(['title' => 'Pedoman', 'pdf_path' => 'guides/guide.pdf', 'is_published' => true]);
        foreach (['pdf', 'download'] as $action) {
            $this->head(route('pedoman-kkprl.'.$action, $guide))->assertOk();
            $this->withHeader('Range', 'bytes=0-20')->get(route('pedoman-kkprl.'.$action, $guide))->assertStatus(206);
            $this->flushHeaders();
        }
        $this->assertSame(0, $guide->fresh()->view_count);
        $this->assertSame(0, $guide->fresh()->download_count);
    }

    public function test_browser_pdf_requests_count_once_per_rendered_view(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('guides/guide.pdf', '%PDF-1.4'.str_repeat('a', 100));
        $guide = ProposalGuide::create(['title' => 'Pedoman', 'pdf_path' => 'guides/guide.pdf', 'is_published' => true]);

        foreach ([1, 2] as $expectedViews) {
            $page = $this->get(route('pedoman-kkprl'))->assertOk();
            preg_match('/<iframe src="([^"]+)"/', $page->getContent(), $matches);
            $pdfUrl = html_entity_decode($matches[1], ENT_QUOTES);
            $this->get($pdfUrl)->assertOk();
            $this->get($pdfUrl)->assertOk();
            $this->assertSame($expectedViews, $guide->fresh()->view_count);
            $this->assertSame(0, $guide->fresh()->download_count);
        }
    }

    public function test_unavailable_documents_do_not_increment_counters(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('guides/draft.pdf', '%PDF-1.4');
        foreach ([
            ['title' => 'Draft', 'pdf_path' => 'guides/draft.pdf', 'is_published' => false],
            ['title' => 'Missing', 'pdf_path' => 'guides/missing.pdf', 'is_published' => true],
        ] as $attributes) {
            $guide = ProposalGuide::create($attributes);
            $this->get(route('pedoman-kkprl.pdf', $guide))->assertNotFound();
            $this->get(route('pedoman-kkprl.download', $guide))->assertNotFound();
            $this->assertSame(0, $guide->fresh()->view_count);
            $this->assertSame(0, $guide->fresh()->download_count);
        }
    }

    public function test_legacy_redirects_count_only_when_the_destination_pdf_is_requested(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('guides/legacy.pdf', '%PDF-1.4');
        $material = $this->legacyMaterial(96, 65);
        $guide = ProposalGuide::create(['title' => $material->title, 'pdf_path' => 'guides/legacy.pdf', 'is_published' => true]);
        $guide->forceFill(['legacy_material_id' => $material->id, 'view_count' => 96, 'download_count' => 65])->save();
        $material->delete();

        foreach (['pdf', 'download'] as $action) {
            $this->get(route('belajar-kkprl.material.'.$action, $material))->assertRedirect(route('pedoman-kkprl.'.$action, $guide));
        }
        $this->assertSame(96, $guide->fresh()->view_count);
        $this->assertSame(65, $guide->fresh()->download_count);
        $this->get(route('pedoman-kkprl.pdf', $guide))->assertOk();
        $this->get(route('pedoman-kkprl.download', $guide))->assertOk();
        $this->assertSame(97, $guide->fresh()->view_count);
        $this->assertSame(66, $guide->fresh()->download_count);
        $this->assertSame(96, $material->fresh()->view_count);
        $this->assertSame(65, $material->fresh()->download_count);
    }

    public function test_admin_table_displays_counters_and_the_content_form_cannot_reset_them(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:LearningMaterial', 'web'));
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('layanankkprl'));
        Filament::bootCurrentPanel();
        $guide = ProposalGuide::create(['title' => 'Pedoman', 'pdf_path' => 'guides/guide.pdf']);
        $guide->forceFill(['view_count' => 96, 'download_count' => 65])->save();

        Livewire::test(ManageProposalGuides::class)
            ->assertTableColumnExists('view_count')->assertTableColumnStateSet('view_count', 96, $guide)
            ->assertTableColumnExists('download_count')->assertTableColumnStateSet('download_count', 65, $guide);
        $guide->fill(['view_count' => 0, 'download_count' => 0, 'title' => 'Judul Baru'])->save();
        $this->assertSame(96, $guide->fresh()->view_count);
        $this->assertSame(65, $guide->fresh()->download_count);
    }

    public function test_counter_migration_restores_the_legacy_baseline_and_preserves_larger_history(): void
    {
        if (Schema::hasColumn('proposal_guides', 'view_count')) {
            Schema::table('proposal_guides', fn ($table) => $table->dropColumn(['view_count', 'download_count']));
        }
        $material = $this->legacyMaterial(12, 5);
        $linked = ProposalGuide::create(['title' => 'Pedoman Berganti Judul', 'pdf_path' => 'guides/old.pdf']);
        $linked->forceFill(['legacy_material_id' => $material->id])->save();
        $material->delete();
        $named = ProposalGuide::create(['title' => 'Pedoman Perolehan Data Proposal KKPRL Skala Kecil', 'pdf_path' => 'guides/manual.pdf']);
        $other = ProposalGuide::create(['title' => 'Dokumen Baru', 'pdf_path' => 'guides/other.pdf']);
        $migration = require database_path('migrations/2026_10_10_000001_add_counters_to_proposal_guides_table.php');
        $migration->up();

        foreach ([$linked, $named] as $guide) {
            $this->assertSame(96, $guide->fresh()->view_count);
            $this->assertSame(65, $guide->fresh()->download_count);
        }
        $this->assertSame(0, $other->fresh()->view_count);
        $this->assertSame(0, $other->fresh()->download_count);
        $this->assertSame(12, $material->fresh()->view_count);
        $this->assertSame(5, $material->fresh()->download_count);
        $migration->down();
        $material->forceFill(['view_count' => 120, 'download_count' => 80])->save();
        $migration->up();
        $this->assertSame(120, $linked->fresh()->view_count);
        $this->assertSame(80, $linked->fresh()->download_count);
    }

    private function legacyMaterial(int $views, int $downloads): LearningMaterial
    {
        $category = LearningCategory::create(['name' => 'Data Pesisir', 'is_active' => true]);
        $group = LearningGroup::create(['title' => 'Data Lapangan', 'learning_category_id' => $category->id, 'is_published' => true]);

        return LearningMaterial::create([
            'learning_group_id' => $group->id,
            'title' => 'Panduan Perolehan Data Oseanografi Ekosistem Pesisir Batimetri dan Pemetaan Dasar bagi Sektor Perikanan Skala Kecil serta Tingkat Resiko Rendah',
            'type' => 'pdf', 'pdf_path' => 'learning-materials/legacy.pdf', 'is_published' => true,
            'view_count' => $views, 'download_count' => $downloads,
        ]);
    }
}
