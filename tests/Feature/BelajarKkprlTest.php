<?php

namespace Tests\Feature;

use App\Filament\Layanankkprl\Resources\LearningAccesses\LearningAccessResource;
use App\Filament\Layanankkprl\Resources\LearningActivities\LearningActivityResource;
use App\Filament\Layanankkprl\Resources\LearningCategories\LearningCategoryResource;
use App\Filament\Layanankkprl\Resources\LearningGroups\LearningGroupResource;
use App\Filament\Layanankkprl\Resources\LearningMaterials\LearningMaterialResource;
use App\Filament\Layanankkprl\Resources\LearningSessions\LearningSessionResource;
use App\Livewire\BelajarKkprl;
use App\Livewire\BelajarKkprlGroup;
use App\Models\LearningActivityLog;
use App\Models\LearningCategory;
use App\Models\LearningGroup;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialAccess;
use App\Models\LearningSession;
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

        $access = $this->postJson(route('learning.access.store'), [
            'learning_group_id' => $group->id,
            'group_key' => $group->accessKey(),
            'name' => 'Junex',
            'institution' => 'Example Institution',
            'access_purpose' => 'Belajar mandiri',
        ])->assertCreated();

        $accessUuid = $access->json('access_uuid');

        $this->get(route('belajar-kkprl.material.pdf', [$pdf, 'access_uuid' => $accessUuid]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="mangrove-pdf.pdf"');

        $this->assertSame(3, $pdf->refresh()->view_count);
        $this->assertSame(3, $pdf->download_count);

        $this->get(route('belajar-kkprl.material.download', [$pdf, 'access_uuid' => $accessUuid]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=mangrove-pdf.pdf');

        $this->assertSame(3, $pdf->refresh()->view_count);
        $this->assertSame(4, $pdf->download_count);
    }

    #[Test]
    public function visitor_access_is_scoped_to_one_group_while_activities_track_each_material(): void
    {
        config(['learning.detailed_tracking_enabled' => true]);

        Storage::fake('public');
        Storage::disk('public')->put('learning-materials/one.pdf', 'one');
        Storage::disk('public')->put('learning-materials/two.pdf', 'two');

        [, $group] = $this->createPublishedGroup();
        $first = $this->createMaterial($group, 'Materi Satu', attributes: ['pdf_path' => 'learning-materials/one.pdf']);
        $second = $this->createMaterial($group, 'Materi Dua', attributes: ['pdf_path' => 'learning-materials/two.pdf']);
        [, $otherGroup] = $this->createPublishedGroup('Dasar KKPRL', 'Pelatihan Dasar KKPRL');
        $otherMaterial = $this->createMaterial($otherGroup, 'Materi Grup Lain', attributes: ['pdf_path' => 'learning-materials/two.pdf']);

        $response = $this->postJson(route('learning.access.store'), [
            'learning_group_id' => $group->id,
            'group_key' => $group->accessKey(),
            'name' => 'Junex',
            'institution' => 'Example Institution',
            'access_purpose' => 'Referensi kerja',
            'browser_uuid' => '39b38e5a-235f-47fd-b726-30ad0478746d',
        ])->assertCreated()->assertJsonPath('group_key', $group->accessKey());

        $uuid = $response->json('access_uuid');
        $sessionId = $response->json('session_id');

        $this->assertDatabaseCount('learning_material_accesses', 1);
        $this->assertDatabaseCount('learning_sessions', 1);
        $this->getJson(route('learning.access.me', ['access_uuid' => $uuid, 'group_key' => $group->accessKey()]))->assertOk();
        $this->getJson(route('learning.access.me', ['access_uuid' => $uuid, 'group_key' => $otherGroup->accessKey()]))->assertUnprocessable();
        $this->get(route('belajar-kkprl.material.pdf', [$second, 'access_uuid' => $uuid]))->assertOk();
        $this->get(route('belajar-kkprl.material.pdf', [$otherMaterial, 'access_uuid' => $uuid]))->assertForbidden();

        $this->postJson(route('learning.activity.store'), [
            'access_uuid' => $uuid,
            'session_id' => $sessionId,
            'group_key' => $group->accessKey(),
            'material_id' => $first->id,
            'activity_type' => 'scroll_50',
            'progress_percent' => 50,
        ])->assertCreated();

        $this->postJson(route('learning.activity.store'), [
            'access_uuid' => $uuid,
            'session_id' => $sessionId,
            'group_key' => $group->accessKey(),
            'material_id' => $first->id,
            'activity_type' => 'open_material',
        ])->assertCreated();

        $this->postJson(route('learning.activity.store'), [
            'access_uuid' => $uuid,
            'session_id' => $sessionId,
            'group_key' => $group->accessKey(),
            'material_id' => $second->id,
            'activity_type' => 'open_material',
        ])->assertCreated();

        $this->postJson(route('learning.session.end'), [
            'access_uuid' => $uuid,
            'session_id' => $sessionId,
            'group_key' => $group->accessKey(),
        ])->assertOk();

        $this->assertSame(50, LearningActivityLog::first()->progress_percent);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], LearningActivityLog::pluck('material_id')->unique()->values()->all());
        $this->assertNotNull(LearningSession::first()->ended_at);
    }

    #[Test]
    public function detailed_tracking_is_disabled_without_collecting_sessions_or_activities(): void
    {
        config(['learning.detailed_tracking_enabled' => false]);

        [, $group] = $this->createPublishedGroup();
        $material = $this->createMaterial($group, 'Materi Ringan');

        $response = $this->postJson(route('learning.access.store'), [
            'learning_group_id' => $group->id,
            'group_key' => $group->accessKey(),
            'name' => 'Junex',
            'institution' => 'Example Institution',
            'access_purpose' => 'Belajar mandiri',
        ])->assertCreated()->assertJsonPath('session_id', null);

        $uuid = $response->json('access_uuid');

        $this->assertDatabaseCount('learning_material_accesses', 1);
        $this->assertDatabaseCount('learning_sessions', 0);
        $this->assertDatabaseCount('learning_activity_logs', 0);

        $this->postJson(route('learning.session.start'), [
            'access_uuid' => $uuid,
            'group_key' => $group->accessKey(),
        ])->assertNotFound();

        $this->postJson(route('learning.activity.store'), [
            'access_uuid' => $uuid,
            'group_key' => $group->accessKey(),
            'material_id' => $material->id,
            'activity_type' => 'open_material',
        ])->assertNotFound();

        $this->get(route('belajar-kkprl.material.show', [$material, 'access_uuid' => $uuid]))
            ->assertOk()
            ->assertDontSee(route('learning.activity.store'), false)
            ->assertDontSee('heartbeat');

        $this->get(route('belajar-kkprl.material.show', [$material, 'access_uuid' => $uuid]))->assertOk();

        $this->assertDatabaseCount('learning_material_opens', 1);
        $this->assertDatabaseHas('learning_material_opens', [
            'learning_material_access_id' => LearningMaterialAccess::first()->id,
            'material_id' => $material->id,
            'material_title' => 'Materi Ringan',
            'open_count' => 2,
        ]);
        $this->assertSame(
            ['Materi Ringan'],
            LearningAccessResource::openedMaterials(LearningMaterialAccess::first())->pluck('material_title')->all(),
        );

        $this->assertFalse(LearningSessionResource::shouldRegisterNavigation());
        $this->assertFalse(LearningActivityResource::shouldRegisterNavigation());
    }

    #[Test]
    public function group_page_contains_group_scoped_form_and_material_page_never_repeats_it(): void
    {
        [, $group] = $this->createPublishedGroup();
        $material = $this->createMaterial($group, 'Materi Pesisir');

        $this->get(route('belajar-kkprl.group', $group))
            ->assertOk()
            ->assertSee('Data Pengunjung Grup Pembelajaran')
            ->assertSee('Tujuan Mengakses Materi')
            ->assertSee('krl_lms_group_access_${groupKey}', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="phone"', false)
            ->assertDontSee('name="password"', false);

        $this->get(route('belajar-kkprl.material.show', $material))
            ->assertRedirect(route('belajar-kkprl.group', $group));

        $access = $this->postJson(route('learning.access.store'), [
            'learning_group_id' => $group->id,
            'group_key' => $group->accessKey(),
            'name' => 'Junex',
            'institution' => 'Example Institution',
            'access_purpose' => 'Belajar mandiri',
        ])->assertCreated();

        $this->get(route('belajar-kkprl.material.show', [$material, 'access_uuid' => $access->json('access_uuid')]))
            ->assertOk()
            ->assertSee('Semua materi dalam grup ini dapat dibuka tanpa mengisi formulir lagi')
            ->assertDontSee('Data Pengunjung Grup Pembelajaran');
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

        $monitoringResources = [LearningAccessResource::class, LearningSessionResource::class, LearningActivityResource::class];

        foreach ($resources as $resource => $navigationLabel) {
            $this->assertSame('Belajar KKPRL', $resource::getNavigationGroup());
            $this->assertSame($navigationLabel, $resource::getNavigationLabel());
        }

        foreach ($monitoringResources as $resource) {
            $this->assertSame('Monitoring Belajar KKPRL', $resource::getNavigationGroup());
        }

        $this->assertFalse(LearningSessionResource::shouldRegisterNavigation());
        $this->assertFalse(LearningActivityResource::shouldRegisterNavigation());

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

        foreach (['learning-accesses' => 'Akses Grup'] as $path => $label) {
            $this->actingAs($user)
                ->get("http://kawanruanglaut.timurbersinar.com/layananruanglaut/{$path}")
                ->assertOk()
                ->assertSee($label);
        }
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
