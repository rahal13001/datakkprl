<?php

namespace App\Livewire;

use App\Models\SatisfactionSurveyResult;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SatisfactionSurveyResults extends Component
{
    public ?int $selectedYear = null;

    public function mount(): void
    {
        $requestedYear = request()->integer('tahun');
        $availableYears = $this->availableYears();

        $this->selectedYear = $availableYears->contains($requestedYear)
            ? $requestedYear
            : $availableYears->first();
    }

    public function updatedSelectedYear($value): void
    {
        $this->selectedYear = $value ? (int) $value : null;
    }

    public function availableYears(): Collection
    {
        return SatisfactionSurveyResult::published()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');
    }

    public function render()
    {
        $availableYears = $this->availableYears();

        if ($this->selectedYear && ! $availableYears->contains((int) $this->selectedYear)) {
            $this->selectedYear = $availableYears->first();
        }

        $results = SatisfactionSurveyResult::published()
            ->when($this->selectedYear, fn ($query) => $query->where('year', $this->selectedYear))
            ->orderByDesc('year')
            ->orderBy('quarter')
            ->get();

        return view('livewire.satisfaction-survey-results', [
            'availableYears' => $availableYears,
            'results' => $results,
        ]);
    }
}
