<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ActivityPerMonthChart extends ChartWidget
{
    protected ?string $heading = 'Pentek Per Bulan';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';



    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $year = $activeFilter ? (int) $activeFilter : now()->year;

        $data = Activity::query()
            ->select(DB::raw('MONTH(date) as month'), DB::raw('count(distinct activity_code) as total'))
            ->whereYear('date', $year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Ensure all 12 months are present
        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[] = $data[$i] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Activities (Pentek)',
                    'data' => $monthlyData,
                    'borderColor' => '#F59E0B',
                    'fill' => 'start',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        $currentYear = now()->year;
        $years = [];

        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $years[$year] = (string) $year;
        }

        return $years;
    }
}
