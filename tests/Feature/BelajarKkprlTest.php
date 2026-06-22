<?php

namespace Tests\Feature;

use App\Filament\Layanankkprl\Resources\LearningCategories\LearningCategoryResource;
use App\Filament\Layanankkprl\Resources\LearningGroups\LearningGroupResource;
use App\Filament\Layanankkprl\Resources\LearningMaterials\LearningMaterialResource;
use App\Livewire\BelajarKkprl;
use App\Livewire\BelajarKkprlGroup;
use App\Models\LearningCategory;
use App\Models\LearningGroup;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BelajarKkprlTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function learning_models_generate_unique_slugs_and_keep_relationships(): void
    {
        $category = LearningCategory::create([
            'name' => 'Ekosistem Pesisir',
            'description' => 'Materi ekosistem pesisir.',
            'sort_order' => '2',
            'is_active' => 1,
        ]);

        $duplicateCategory = LearningCategory::create([
            'name' => 'Ekosistem Pesisir',
            'is_active' => false,
        ]);

        $group = LearningGroup::create([
            'learning_category_id' => $category->id,
            'title' => 'Pengumpulan Data Ekosistem',
            'description' => 'Tema pengumpulan data.',
            'sort_order' => '3',
            'is_featured' => 1,
            'is_published' => 1,
        ]);

        $material = LearningMaterial::create([
            'learning_group_id' => $group->id,
            'title' => 'Mangrove PDF',
            'description' => 'Referensi mangrove.',
            'type' => LearningMaterial::TYPE_PDF,
            'pdf_path' => 'learning-materials/mangrove.pdf',
            'video_url' => 'https://youtu.be/ignored-for-pdf',
            'sort_order' => '4',
            'is_featured' => 1,
            'is_published' => 1,
            'view_count' => '5',
            'download_count' => '6',
        ]);

        $this->assertSame('ekosistem-pesisir', $category->slug);
        $this->assertSame('ekosistem-pesisir-1', $duplicateCategory->slug);
        $this->assertSame('pengumpulan-data-ekosistem', $group->slug);
        $this->assertSame('mangrove-pdf', $material->slug);

        $this->assertTrue($category->is_active);
        $this->assertTrue($group->is_featured);
        $this->assertTrue($group->is_published);
        $this->assertTrue($material->is_featured);
        $this->assertTrue($material->is_published);
        $this->assertSame(5, $material->view_count);
        $this->assertSame(6, $material->download_count);
        $this->assertNull($material->video_url);
        $this->assertTrue($material->isPdf());

        $this->assertTrue($category->groups->contains($group));
        $this->assertTrue($group->materials->contains($material));
        $this->assertTrue($material->group->is($group));

        $group->delete();

        $this->assertSoftDeleted('learning_groups', ['id' => $group->id]);
    }

    #[Test]
    public function public_learning_page_is_accessible_without_login_and_hides_unpublished_content(): void
    {
        [$category, $group] = $this->createPublishedGroup(
            categoryName: 'Ekosistem Pesisir',
            groupTitle: 'Pengumpulan Data Ekosistem'
        );

        $this->createMaterial($group, title: 'Mangrove PDF', type: LearningMaterial::TYPE_PDF);

        [, $inactiveGroup] = $this->createPublishedGroup(
            categoryName: 'Kategori Nonaktif',
            groupTitle: 'Grup Kategori Nonaktif',
            categoryAttributes: ['is_active' => false]
        );
        $this->createMaterial($inactiveGroup, title: 'Materi Kategori Nonaktif');

        [$publishedCategory] = [$category];
        $unpublishedGroup = LearningGroup::create([
            'learning_category_id' => $publishedCategory->id,
            'title' => 'Grup Belum Terbit',
            'is_published' => false,
        ]);
        $this->createMaterial($unpublishedGroup, title: 'Materi Grup Belum Terbit');

        $this->get('/belajar-kkprl')
            ->assertOk()
            ->assertSee('Belajar KKPRL')
            ->assertSee('Pengumpulan Data Ekosistem')
            ->assertSee('Mangrove PDF')
            ->assertDontSee('Grup Kategori Nonaktif')
            ->assertDontSee('Materi Kategori Nonaktif')
            ->assertDontSee('Grup Belum Terbit')
            ->assertDontSee('Materi Grup Belum Terbit');
    }

    #[Test]
    public function public_search_category_and_type_filters_follow_visibility_rules(): void
    {
        [$coastalCategory, $coastalGroup] = $this->createPublishedGroup(
            categoryName: 'Ekosistem Pesisir',
            groupTitle: 'Pengumpulan Data Ekosistem'
        );
        $this->createMaterial($coastalGroup, title: 'Mangrove PDF', type: LearningMaterial::TYPE_PDF);
        $this->createMaterial($coastalGroup, title: 'Mangrove Video', type: LearningMaterial::TYPE_VIDEO);

        [$permitCategory, $permitGroup] = $this->createPublishedGroup(
            categoryName: 'Perizinan Ruang Laut',
            groupTitle: 'Dokumen Permohonan'
        );
        $this->createMaterial($permitGroup, title: 'Checklist Dokumen', type: LearningMaterial::TYPE_PDF);

        Livewire::test(BelajarKkprl::class)
            ->set('search', 'mangrove')
            ->assertSee('Pengumpulan Data Ekosistem')
            ->assertSee('Mangrove PDF')
            ->assertSee('Mangrove Video')
            ->assertDontSee('Dokumen Permohonan')
            ->set('search', '')
            ->set('selectedCategory', (string) $permitCategory->id)
            ->assertSee('Dokumen Permohonan')
            ->assertSee('Checklist Dokumen')
            ->assertDontSee('Pengumpulan Data Ekosistem')
            ->set('selectedCategory', (string) $coastalCategory->id)
            ->set('selectedType', LearningMaterial::TYPE_VIDEO)
            ->assertSee('Pengumpulan Data Ekosistem')
            ->assertSee('Mangrove Video')
            ->assertDontSee('Mangrove PDF');
    }

    #[Test]
    public function group_page_shows_only_published_materials_and_blocks_hidden_groups(): void
    {
        [, $group] = $this->createPublishedGroup(groupTitle: 'Pengumpulan Data Ekosistem');
        $this->createMaterial($group, title: 'Mangrove PDF');
        $this->createMaterial($group, title: 'Draft Rahasia', attributes: ['is_published' => false]);

        [, $unpublishedGroup] = $this->createPublishedGroup(
            groupTitle: 'Grup Belum Terbit',
            groupAttributes: ['is_published' => false]
        );

        $this->get(route('belajar-kkprl.group', $group))
            ->assertOk()
            ->assertSee('Pengumpulan Data Ekosistem')
            ->assertSee('Mangrove PDF')
            ->assertDontSee('Draft Rahasia');

        $this->get(route('belajar-kkprl.group', $unpublishedGroup))
            ->assertNotFound();
    }

    #[Test]
    public function pdf_routes_open_inline_download_files_and_increment_counters(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('learning-materials/mangrove.pdf', "%PDF-1.4\nBelajar KKPRL test PDF");

        [, $group] = $this->createPublishedGroup();
        $pdf = $this->createMaterial($group, title: 'Mangrove PDF', attributes: [
            'pdf_path' => 'learning-materials/mangrove.pdf',
            'view_count' => 2,
            'download_count' => 3,
        ]);

        $this->get(route('belajar-kkprl.material.pdf', $pdf))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="mangrove-pdf.pdf"');

        $this->assertSame(3, $pdf->refresh()->view_count);
        $this->assertSame(3, $pdf->download_count);

        $this->get(route('belajar-kkprl.material.download', $pdf))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=mangrove-pdf.pdf');

        $this->assertSame(3, $pdf->refresh()->view_count);
        $this->assertSame(4, $pdf->download_count);
    }

    #[Test]
    public function unpublished_missing_and_video_materials_cannot_use_pdf_routes(): void
    {
        Storage::fake('public');

        [, $group] = $this->createPublishedGroup();
        $unpublished = $this->createMaterial($group, title: 'Draft PDF', attributes: ['is_published' => false]);
        $missingFile = $this->createMaterial($group, title: 'Missing PDF');
        $video = $this->createMaterial($group, title: 'Mangrove Video', type: LearningMaterial::TYPE_VIDEO);

        $this->get(route('belajar-kkprl.material.pdf', $unpublished))->assertNotFound();
        $this->get(route('belajar-kkprl.material.pdf', $missingFile))->assertNotFound();
        $this->get(route('belajar-kkprl.material.pdf', $video))->assertNotFound();
        $this->get(route('belajar-kkprl.material.download', $video))->assertNotFound();
    }

    #[Test]
    public function opening_video_materials_from_group_page_increments_view_count(): void
    {
        [, $group] = $this->createPublishedGroup();
        $video = $this->createMaterial($group, title: 'Mangrove Video', type: LearningMaterial::TYPE_VIDEO, attributes: [
            'video_url' => 'https://youtu.be/abc123',
            'view_count' => 7,
        ]);

        Livewire::test(BelajarKkprlGroup::class, ['group' => $group])
            ->call('showVideo', $video->slug)
            ->assertSet('activeVideoSlug', $video->slug);

        $this->assertSame(8, $video->refresh()->view_count);
    }

    #[Test]
    public function learning_admin_resources_are_registered_in_layanankkprl_navigation_group(): void
    {
        $resources = [
            LearningCategoryResource::class => 'Kategori Pembelajaran',
            LearningGroupResource::class => 'Grup Pembelajaran',
            LearningMaterialResource::class => 'Materi Pembelajaran',
        ];

        foreach ($resources as $resource => $navigationLabel) {
            $this->assertSame('Belajar KKPRL', $resource::getNavigationGroup());
            $this->assertSame($navigationLabel, $resource::getNavigationLabel());
        }

        $this->assertStringContainsString(
            '/layananruanglaut/learning-categories',
            route('filament.layanankkprl.resources.learning-categories.index')
        );
        $this->assertStringContainsString(
            '/layananruanglaut/learning-groups',
            route('filament.layanankkprl.resources.learning-groups.index')
        );
        $this->assertStringContainsString(
            '/layananruanglaut/learning-materials',
            route('filament.layanankkprl.resources.learning-materials.index')
        );
    }

    #[Test]
    public function learning_admin_index_pages_load_for_authorized_users(): void
    {
        config(['app.env' => 'local']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        Gate::before(fn (User $user): bool => true);

        foreach (['LearningCategory', 'LearningGroup', 'LearningMaterial'] as $model) {
            Permission::findOrCreate("ViewAny:{$model}", 'web');
            $user->givePermissionTo("ViewAny:{$model}");
        }

        $this->actingAs($user)
            ->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/learning-categories')
            ->assertOk()
            ->assertSee('Kategori Pembelajaran');

        $this->actingAs($user)
            ->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/learning-groups')
            ->assertOk()
            ->assertSee('Grup Pembelajaran');

        $this->actingAs($user)
            ->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/learning-materials')
            ->assertOk()
            ->assertSee('Materi Pembelajaran');
    }

    private function createPublishedGroup(
        string $categoryName = 'Ekosistem Pesisir',
        string $groupTitle = 'Pengumpulan Data Ekosistem',
        array $categoryAttributes = [],
        array $groupAttributes = [],
    ): array {
        $category = LearningCategory::create(array_merge([
            'name' => $categoryName,
            'description' => "Deskripsi {$categoryName}",
            'is_active' => true,
            'sort_order' => 1,
        ], $categoryAttributes));

        $group = LearningGroup::create(array_merge([
            'learning_category_id' => $category->id,
            'title' => $groupTitle,
            'description' => "Deskripsi {$groupTitle}",
            'is_featured' => false,
            'is_published' => true,
            'sort_order' => 1,
        ], $groupAttributes));

        return [$category, $group];
    }

    private function createMaterial(
        LearningGroup $group,
        string $title,
        string $type = LearningMaterial::TYPE_PDF,
        array $attributes = [],
    ): LearningMaterial {
        return LearningMaterial::create(array_merge([
            'learning_group_id' => $group->id,
            'title' => $title,
            'description' => "Deskripsi {$title}",
            'type' => $type,
            'pdf_path' => $type === LearningMaterial::TYPE_PDF ? 'learning-materials/mangrove.pdf' : null,
            'video_url' => $type === LearningMaterial::TYPE_VIDEO ? 'https://www.youtube.com/watch?v=abc123' : null,
            'thumbnail_path' => null,
            'is_featured' => false,
            'is_published' => true,
            'sort_order' => 1,
            'view_count' => 0,
            'download_count' => 0,
        ], $attributes));
    }
}
