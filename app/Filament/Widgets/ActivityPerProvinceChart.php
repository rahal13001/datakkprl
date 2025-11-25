<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ActivityPerProvinceChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Pentek Per Provinsi';

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        $data = Activity::query()
            ->when($startDate, fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->join('cities', 'activities.city_id', '=', 'cities.id')
            ->join('provinces', 'cities.province_id', '=', 'provinces.id')
            ->select('provinces.name', DB::raw('count(distinct activities.activity_code) as total'))
            ->groupBy('provinces.name')
            ->pluck('total', 'provinces.name')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Activities (Pentek)',
                    'data' => array_values($data),
                    'backgroundColor' => '#36A2EB',
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
