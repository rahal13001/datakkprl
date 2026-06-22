<?php

namespace App\Livewire;

use App\Models\LearningCategory;
use App\Models\LearningGroup;
use App\Models\LearningMaterial;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class BelajarKkprl extends Component
{
    public string $search = '';

    public string $selectedCategory = '';

    public string $selectedType = '';

    public function updatedSelectedCategory($value): void
    {
        $this->selectedCategory = (string) $value;
    }

    public function updatedSelectedType($value): void
    {
        $this->selectedType = (string) $value;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedCategory = '';
        $this->selectedType = '';
    }

    public function render()
    {
        $categories = LearningCategory::active()
            ->withCount([
                'groups as published_groups_count' => fn (Builder $query) => $query->published(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $groups = $this->filteredGroupsQuery()
            ->with([
                'category',
                'materials' => fn ($query) => $query->published()->orderBy('sort_order')->orderBy('title'),
            ])
            ->withCount([
                'materials as published_materials_count' => fn (Builder $query) => $query->published(),
            ])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $featuredGroups = $groups
            ->where('is_featured', true)
            ->take(3)
            ->values();

        $materials = $this->filteredMaterialsQuery()
            ->with(['group.category'])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return view('livewire.belajar-kkprl', [
            'categories' => $categories,
            'groups' => $groups,
            'featuredGroups' => $featuredGroups,
            'materials' => $materials,
        ]);
    }

    protected function filteredGroupsQuery(): Builder
    {
        return LearningGroup::query()
            ->publiclyVisible()
            ->when(
                filled($this->selectedCategory),
                fn (Builder $query) => $query->where('learning_category_id', (int) $this->selectedCategory)
            )
            ->when(
                in_array($this->selectedType, [LearningMaterial::TYPE_PDF, LearningMaterial::TYPE_VIDEO], true),
                fn (Builder $query) => $query->whereHas(
                    'materials',
                    fn (Builder $query) => $query->published()->where('type', $this->selectedType)
                )
            )
            ->when(filled(trim($this->search)), function (Builder $query): void {
                $search = trim($this->search);

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('materials', function (Builder $query) use ($search): void {
                            $query->published()
                                ->where(function (Builder $query) use ($search): void {
                                    $query->where('title', 'like', "%{$search}%")
                                        ->orWhere('description', 'like', "%{$search}%");
                                });
                        });
                });
            });
    }

    protected function filteredMaterialsQuery(): Builder
    {
        return LearningMaterial::query()
            ->publiclyVisible()
            ->when(
                filled($this->selectedCategory),
                fn (Builder $query) => $query->whereHas(
                    'group',
                    fn (Builder $query) => $query->where('learning_category_id', (int) $this->selectedCategory)
                )
            )
            ->when(
                in_array($this->selectedType, [LearningMaterial::TYPE_PDF, LearningMaterial::TYPE_VIDEO], true),
                fn (Builder $query) => $query->where('type', $this->selectedType)
            )
            ->when(filled(trim($this->search)), function (Builder $query): void {
                $search = trim($this->search);

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('group', function (Builder $query) use ($search): void {
                            $query->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%")
                                ->orWhereHas('category', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                        });
                });
            });
    }
}
