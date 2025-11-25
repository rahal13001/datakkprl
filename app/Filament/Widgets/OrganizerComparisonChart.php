<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OrganizerComparisonChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Perbandingan Penyelenggara';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        $data = Activity::query()
            ->when($startDate, fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->select('organizer', DB::raw('count(distinct activity_code) as total'))
            ->groupBy('organizer')
            ->pluck('total', 'organizer')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Activities (Pentek)',
                    'data' => array_values($data),
                    'backgroundColor' => ['#3B82F6', '#F59E0B'],
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
