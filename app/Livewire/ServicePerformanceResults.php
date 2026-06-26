<?php

namespace App\Livewire;

use App\Models\PublicFeedback;
use App\Models\ServicePerformanceResult;
use App\Services\StaffPerformanceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ServicePerformanceResults extends Component
{
    public ?int $selectedYear = null;

    public ?int $selectedQuarter = null;

    public ?int $tableYear = null;

    public ?int $tableQuarter = null;

    public function mount(): void
    {
        $requestedYear = request()->integer('tahun');
        $requestedQuarter = request()->integer('triwulan');
        $availableYears = $this->availableYears();

        $this->selectedYear = $availableYears->contains($requestedYear)
            ? $requestedYear
            : $availableYears->first();

        $availableQuarters = $this->availableQuarters();

        $this->selectedQuarter = $availableQuarters->contains($requestedQuarter)
            ? $requestedQuarter
            : $availableQuarters->first();

        $tableYears = $this->availableTableYears();
        $this->tableYear = $tableYears->contains($requestedYear)
            ? $requestedYear
            : $tableYears->first();

        $tableQuarters = $this->availableTableQuarters();
        $this->tableQuarter = $tableQuarters->contains($requestedQuarter)
            ? $requestedQuarter
            : null;
    }

    public function updatedSelectedYear($value): void
    {
        $this->selectedYear = $value ? (int) $value : null;
        $this->selectedQuarter = $this->availableQuarters()->first();
    }

    public function updatedSelectedQuarter($value): void
    {
        $this->selectedQuarter = $value ? (int) $value : null;
    }

    public function updatedTableYear($value): void
    {
        $this->tableYear = $value ? (int) $value : null;
        $this->tableQuarter = null;
    }

    public function updatedTableQuarter($value): void
    {
        $this->tableQuarter = $value ? (int) $value : null;
    }

    public function availableYears(): Collection
    {
        $publishedYears = ServicePerformanceResult::published()
            ->select('year')
            ->distinct()
            ->pluck('year');

        return $publishedYears
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->whenEmpty(fn (Collection $years) => $years->push(now()->year));
    }

    public function availableTableYears(): Collection
    {
        return DB::table('schedules')
            ->join('assignments', 'assignments.schedule_id', '=', 'schedules.id')
            ->join('users', 'users.id', '=', 'assignments.user_id')
            ->whereNull('schedules.deleted_at')
            ->whereNull('assignments.deleted_at')
            ->where('users.status', true)
            ->pluck('schedules.date')
            ->map(fn ($date): int => Carbon::parse($date)->year)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->whenEmpty(fn (Collection $years) => $years->push(now()->year));
    }

    public function availableQuarters(): Collection
    {
        if (! $this->selectedYear) {
            return collect([now()->quarter]);
        }

        $publishedQuarters = ServicePerformanceResult::published()
            ->where('year', $this->selectedYear)
            ->select('quarter')
            ->distinct()
            ->pluck('quarter');

        return $publishedQuarters
            ->filter()
            ->map(fn ($quarter) => (int) $quarter)
            ->unique()
            ->sort()
            ->values()
            ->whenEmpty(fn (Collection $quarters) => $quarters->push(now()->quarter));
    }

    public function availableTableQuarters(): Collection
    {
        if (! $this->tableYear) {
            return collect([now()->quarter]);
        }

        return DB::table('schedules')
            ->join('assignments', 'assignments.schedule_id', '=', 'schedules.id')
            ->join('users', 'users.id', '=', 'assignments.user_id')
            ->whereYear('schedules.date', $this->tableYear)
            ->whereNull('schedules.deleted_at')
            ->whereNull('assignments.deleted_at')
            ->where('users.status', true)
            ->pluck('schedules.date')
            ->map(fn ($date): int => Carbon::parse($date)->quarter)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->whenEmpty(fn (Collection $quarters) => $quarters->push(now()->quarter));
    }

    public function render()
    {
        $performanceService = app(StaffPerformanceService::class);
        $availableYears = $this->availableYears();
        $availableTableYears = $this->availableTableYears();

        if ($this->selectedYear && ! $availableYears->contains((int) $this->selectedYear)) {
            $this->selectedYear = $availableYears->first();
        }

        $availableQuarters = $this->availableQuarters();

        if ($this->selectedQuarter && ! $availableQuarters->contains((int) $this->selectedQuarter)) {
            $this->selectedQuarter = $availableQuarters->first();
        }

        if ($this->tableYear && ! $availableTableYears->contains((int) $this->tableYear)) {
            $this->tableYear = $availableTableYears->first();
        }

        $availableTableQuarters = $this->availableTableQuarters();

        if ($this->tableQuarter && ! $availableTableQuarters->contains((int) $this->tableQuarter)) {
            $this->tableQuarter = null;
        }

        [$from, $until] = $this->quarterDateRange();
        [$tableFrom, $tableUntil] = $this->tableDateRange();

        $result = ServicePerformanceResult::published()
            ->where('year', $this->selectedYear)
            ->where('quarter', $this->selectedQuarter)
            ->first();

        $publicFeedbackMentionCounts = DB::table('public_feedback')
            ->join('public_feedback_user', 'public_feedback_user.public_feedback_id', '=', 'public_feedback.id')
            ->when(
                $tableFrom,
                fn ($query) => $query->whereDate('public_feedback.created_at', '>=', $tableFrom),
            )
            ->when(
                $tableUntil,
                fn ($query) => $query->whereDate('public_feedback.created_at', '<=', $tableUntil),
            )
            ->selectRaw('public_feedback_user.user_id, COUNT(DISTINCT public_feedback.id) as total')
            ->groupBy('public_feedback_user.user_id')
            ->pluck('total', 'public_feedback_user.user_id');

        $publicFeedbackBaseQuery = $this->publicFeedbackQuery($tableFrom, $tableUntil);

        $staffRows = $performanceService->summaryQuery($tableFrom, $tableUntil)
            ->orderByDesc('service_activities_count')
            ->get()
            ->map(function ($row) use ($publicFeedbackMentionCounts) {
                $row->public_feedback_mentions_count = (int) ($publicFeedbackMentionCounts[$row->id] ?? 0);

                return $row;
            })
            ->filter(fn ($row): bool => (int) $row->service_activities_count > 0
                || (int) $row->rated_sessions > 0
                || (int) $row->public_feedback_mentions_count > 0)
            ->values();

        $publicFeedbackTotalCount = (clone $publicFeedbackBaseQuery)->count();
        $publicFeedbackGeneralCount = (clone $publicFeedbackBaseQuery)
            ->doesntHave('users')
            ->count();
        $recentPublicFeedbacks = (clone $publicFeedbackBaseQuery)
            ->with('users:id,name')
            ->latest()
            ->take(6)
            ->get();

        return view('livewire.service-performance-results', [
            'availableYears' => $availableYears,
            'availableQuarters' => $availableQuarters,
            'availableTableYears' => $availableTableYears,
            'availableTableQuarters' => $availableTableQuarters,
            'result' => $result,
            'staffRows' => $staffRows,
            'periodLabel' => $this->periodLabel(),
            'tablePeriodLabel' => $this->tablePeriodLabel(),
            'performanceService' => $performanceService,
            'publicFeedbackTotalCount' => $publicFeedbackTotalCount,
            'publicFeedbackGeneralCount' => $publicFeedbackGeneralCount,
            'recentPublicFeedbacks' => $recentPublicFeedbacks,
        ]);
    }

    protected function quarterDateRange(): array
    {
        $year = $this->selectedYear ?: now()->year;
        $quarter = $this->selectedQuarter ?: now()->quarter;
        $startMonth = (($quarter - 1) * 3) + 1;
        $start = Carbon::create($year, $startMonth, 1)->startOfDay();
        $end = (clone $start)->addMonths(2)->endOfMonth()->endOfDay();

        return [$start->toDateString(), $end->toDateString()];
    }

    protected function tableDateRange(): array
    {
        $year = $this->tableYear ?: now()->year;

        if (! $this->tableQuarter) {
            return [
                Carbon::create($year, 1, 1)->startOfYear()->toDateString(),
                Carbon::create($year, 12, 31)->endOfYear()->toDateString(),
            ];
        }

        $startMonth = (($this->tableQuarter - 1) * 3) + 1;
        $start = Carbon::create($year, $startMonth, 1)->startOfDay();
        $end = (clone $start)->addMonths(2)->endOfMonth()->endOfDay();

        return [$start->toDateString(), $end->toDateString()];
    }

    protected function periodLabel(): string
    {
        $quarter = ServicePerformanceResult::QUARTERS[$this->selectedQuarter] ?? 'Triwulan '.$this->selectedQuarter;

        return "{$quarter} {$this->selectedYear}";
    }

    protected function tablePeriodLabel(): string
    {
        if (! $this->tableQuarter) {
            return 'Tahun '.$this->tableYear;
        }

        $quarter = ServicePerformanceResult::QUARTERS[$this->tableQuarter] ?? 'Triwulan '.$this->tableQuarter;

        return "{$quarter} {$this->tableYear}";
    }

    protected function publicFeedbackQuery(?string $from, ?string $until): Builder
    {
        return PublicFeedback::query()
            ->when(
                $from,
                fn (Builder $query) => $query->whereDate('created_at', '>=', $from),
            )
            ->when(
                $until,
                fn (Builder $query) => $query->whereDate('created_at', '<=', $until),
            );
    }
}
