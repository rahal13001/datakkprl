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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProposalGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_moves_only_the_legacy_guide_preserves_history_and_redirects_old_urls(): void
    {
        Schema::drop('proposal_guides');
        $category = LearningCategory::create(['name' => 'Data Pesisir', 'is_active' => true]);
        $group = LearningGroup::create(['title' => 'Data Lapangan', 'learning_category_id' => $category->id, 'is_published' => true]);
        $material = LearningMaterial::create([
            'learning_group_id' => $group->id,
            'title' => 'Panduan Perolehan Data Oseanografi Ekosistem Pesisir Batimetri dan Pemetaan Dasar bagi Sektor Perikanan Skala Kecil serta Tingkat Resiko Rendah',
            'type' => 'pdf', 'pdf_path' => 'learning-materials/legacy.pdf', 'is_published' => true,
            'view_count' => 12, 'download_count' => 5,
        ]);
        $other = LearningMaterial::create(['learning_group_id' => $group->id, 'title' => 'Materi Lain', 'type' => 'pdf', 'pdf_path' => 'other.pdf', 'is_published' => true]);
        $migration = require database_path('migrations/2026_10_06_000000_create_proposal_guides_table.php');
        $migration->up();

        $guide = ProposalGuide::firstOrFail();
        $this->assertSame($material->pdf_path, $guide->pdf_path);
        $this->assertTrue($guide->is_published);
        $this->assertSoftDeleted($material);
        $this->assertFalse($other->refresh()->trashed());
        $this->assertSame(12, $material->refresh()->view_count);
        $this->assertSame(5, $material->download_count);
        $this->get('/belajar-kkprl')->assertDontSee($material->title)->assertSee('Materi Lain');
        $this->get(route('belajar-kkprl.group', $group))->assertDontSee($material->title);
        $this->get(route('belajar-kkprl.material.show', [$material, 'access_uuid' => 'obsolete']))->assertRedirect(route('pedoman-kkprl').'#pedoman-'.$guide->id);
        $this->get(route('belajar-kkprl.material.pdf', $material))->assertRedirect(route('pedoman-kkprl.pdf', $guide));
        $this->get(route('belajar-kkprl.material.download', $material))->assertRedirect(route('pedoman-kkprl.download', $guide));
        $guide->update(['is_published' => false]);
        $this->get(route('belajar-kkprl.material.show', $material))->assertNotFound();

        $migration->down();
        $this->assertFalse($material->refresh()->trashed());
        $this->assertTrue($material->is_published);
    }

    public function test_archived_regular_learning_material_cannot_be_opened(): void
    {
        $category = LearningCategory::create(['name' => 'Pesisir', 'is_active' => true]);
        $group = LearningGroup::create(['title' => 'Materi Pesisir', 'learning_category_id' => $category->id, 'is_published' => true]);
        $material = LearningMaterial::create(['learning_group_id' => $group->id, 'title' => 'Arsip', 'type' => 'pdf', 'pdf_path' => 'archive.pdf', 'is_published' => true]);
        $material->delete();
        foreach (['show', 'pdf', 'download'] as $action) {
            $this->get(route('belajar-kkprl.material.'.$action, $material))->assertNotFound();
        }
    }

    public function test_authorized_admin_can_add_and_update_pdf_through_the_admin_form(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        foreach (['ViewAny', 'Create', 'Update'] as $action) {
            $user->givePermissionTo(Permission::findOrCreate($action.':LearningMaterial', 'web'));
        }
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('layanankkprl'));
        Filament::bootCurrentPanel();

        Livewire::test(ManageProposalGuides::class)->callAction('create', data: [
            'title' => 'Pedoman dari Admin', 'description' => 'Deskripsi awal',
            'pdf_path' => UploadedFile::fake()->create('guide.pdf', 100, 'application/pdf'),
            'sort_order' => 1, 'is_published' => true,
        ])->assertHasNoActionErrors();
        $guide = ProposalGuide::firstOrFail();
        $oldPath = $guide->pdf_path;
        Storage::disk('public')->assertExists($oldPath);

        Livewire::test(ManageProposalGuides::class)
            ->mountTableAction('edit', $guide)
            ->fillForm(['pdf_path' => []])
            ->fillForm([
                'title' => 'Pedoman Diperbarui', 'description' => 'Deskripsi baru',
                'pdf_path' => UploadedFile::fake()->create('updated.pdf', 100, 'application/pdf'),
                'sort_order' => 3, 'is_published' => true,
            ])->callMountedTableAction()->assertHasNoTableActionErrors();
        $this->assertSame('Pedoman Diperbarui', $guide->refresh()->title);
        $this->assertNotSame($oldPath, $guide->refresh()->pdf_path);
        Storage::disk('public')->assertExists($guide->pdf_path);
        Storage::disk('public')->assertExists($oldPath);
        $this->get('/pedoman-kkprl')->assertSee('Pedoman Diperbarui')->assertSee('Deskripsi baru');
    }

    public function test_admin_access_and_actions_require_existing_material_permissions(): void
    {
        $user = User::factory()->create();
        $guide = ProposalGuide::create(['title' => 'Pedoman', 'pdf_path' => 'guides/guide.pdf']);
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', ProposalGuide::class));
        $this->assertFalse(Gate::forUser($user)->allows('create', ProposalGuide::class));
        $this->assertFalse(Gate::forUser($user)->allows('update', $guide));
        $this->assertFalse(Gate::forUser($user)->allows('delete', $guide));
        config(['app.env' => 'local']);
        $this->actingAs($user)->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/proposal-guides')->assertForbidden();
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:LearningMaterial', 'web'));
        $this->assertTrue(Gate::forUser($user)->allows('viewAny', ProposalGuide::class));
        $this->assertFalse(Gate::forUser($user)->allows('update', $guide));
        $this->actingAs($user)->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/proposal-guides')->assertOk()->assertSee('Pedoman KKPRL');
    }

    public function test_admin_form_rejects_non_pdf_oversized_and_forged_paths(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        foreach (['ViewAny', 'Create'] as $action) {
            $user->givePermissionTo(Permission::findOrCreate($action.':LearningMaterial', 'web'));
        }
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('layanankkprl'));
        Filament::bootCurrentPanel();

        foreach ([
            UploadedFile::fake()->create('image.png', 100, 'image/png'),
            UploadedFile::fake()->create('oversized.pdf', 10241, 'application/pdf'),
            ['clients/other-document.pdf'],
        ] as $file) {
            Livewire::test(ManageProposalGuides::class)->callAction('create', data: [
                'title' => 'Invalid Guide', 'pdf_path' => $file, 'sort_order' => 0, 'is_published' => true,
            ])->assertHasActionErrors(['pdf_path']);
        }
        $this->assertDatabaseCount('proposal_guides', 0);
    }

    public function test_public_page_is_independent_and_shows_only_published_guides_in_order(): void
    {
        ProposalGuide::create(['title' => 'Pedoman Kedua', 'pdf_path' => 'guides/two.pdf', 'sort_order' => 2, 'is_published' => true]);
        ProposalGuide::create(['title' => 'Pedoman Pertama', 'pdf_path' => 'guides/one.pdf', 'sort_order' => 1, 'is_published' => true]);
        ProposalGuide::create(['title' => 'Draft Rahasia', 'pdf_path' => 'guides/draft.pdf', 'is_published' => false]);

        $this->get('/pedoman-kkprl')->assertOk()
            ->assertSeeInOrder(['Pedoman Pertama', 'Pedoman Kedua'])
            ->assertDontSee('Draft Rahasia')
            ->assertDontSee('access_uuid')
            ->assertDontSee('Data Pengunjung Grup Pembelajaran');
    }

    public function test_pdf_and_download_follow_updates_without_changing_the_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('guides/old.pdf', "%PDF-1.4\nold");
        Storage::disk('public')->put('guides/new.pdf', "%PDF-1.4\nnew");
        $guide = ProposalGuide::create(['title' => 'Pedoman Lama', 'pdf_path' => 'guides/old.pdf', 'is_published' => true]);
        $url = route('pedoman-kkprl.pdf', $guide);
        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');

        $guide->update(['title' => 'Pedoman Baru', 'pdf_path' => 'guides/new.pdf']);
        $response = $this->get($url)->assertOk()->assertHeader('content-disposition', 'inline; filename="pedoman-baru.pdf"');
        $this->assertSame(Storage::disk('public')->path('guides/new.pdf'), $response->baseResponse->getFile()->getPathname());
        $this->get(route('pedoman-kkprl.download', $guide))->assertOk()->assertDownload('pedoman-baru.pdf');
        $this->get('/pedoman-kkprl')->assertSee('Pedoman Baru')->assertDontSee('Pedoman Lama');
    }

    public function test_draft_and_missing_files_are_not_served_and_empty_page_remains_useful(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('guides/draft.pdf', '%PDF-1.4');
        $draft = ProposalGuide::create(['title' => 'Draft', 'pdf_path' => 'guides/draft.pdf', 'is_published' => false]);
        $missing = ProposalGuide::create(['title' => 'Missing', 'pdf_path' => 'guides/missing.pdf', 'is_published' => true]);
        foreach ([$draft, $missing] as $guide) {
            $this->get(route('pedoman-kkprl.pdf', $guide))->assertNotFound();
            $this->get(route('pedoman-kkprl.download', $guide))->assertNotFound();
        }
        $missing->update(['is_published' => false]);
        $this->get('/pedoman-kkprl')->assertOk()->assertSee('Pedoman sedang diperbarui');
    }
}
