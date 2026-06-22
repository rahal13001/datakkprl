<?php

namespace App\Livewire;

use App\Models\LearningGroup;
use App\Models\LearningMaterial;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class BelajarKkprlGroup extends Component
{
    public LearningGroup $group;

    public ?string $activeVideoSlug = null;

    public function mount(LearningGroup $group): void
    {
        abort_unless($group->is_published && $group->category?->is_active, 404);

        $this->group = $group->load([
            'category',
            'materials' => fn ($query) => $query->published()->orderBy('sort_order')->orderBy('title'),
        ]);
    }

    public function render()
    {
        return view('livewire.belajar-kkprl-group');
    }

    public function showVideo(string $slug): void
    {
        $material = $this->publicVideoMaterial($slug);

        LearningMaterial::whereKey($material->getKey())->increment('view_count');

        $this->activeVideoSlug = $material->slug;

        $this->group->load([
            'category',
            'materials' => fn ($query) => $query->published()->orderBy('sort_order')->orderBy('title'),
        ]);
    }

    public function openVideo(string $slug)
    {
        $material = $this->publicVideoMaterial($slug);

        LearningMaterial::whereKey($material->getKey())->increment('view_count');

        return redirect()->away($material->safeVideoUrl());
    }

    protected function publicVideoMaterial(string $slug): LearningMaterial
    {
        $material = $this->group->materials()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($material->isVideo() && $material->safeVideoUrl(), 404);

        return $material;
    }
}
