<?php

namespace App\Filament\Layanankkprl\Widgets;

use App\Models\Client;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyServiceChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Tren Layanan Bulanan';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('start_date')
                ->label('Dari')
                ->default(Carbon::now()->startOfYear()),
            DatePicker::make('end_date')
                ->label('Sampai')
                ->default(Carbon::now()),
        ]);
    }

    protected function getData(): array
    {
        $start = Carbon::parse($this->filters['start_date'] ?? Carbon::now()->startOfYear()->toDateString())->startOfDay();
        $end = Carbon::parse($this->filters['end_date'] ?? Carbon::now()->toDateString())->endOfDay();

        // Build month labels and data between start and end
        $months = [];
        $current = $start->copy()->startOfMonth();
        while ($current->lte($end)) {
            $months[$current->format('Y-m')] = 0;
            $current->addMonth();
        }

        // Query monthly counts
        $results = Client::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Merge results into full month range
        foreach ($results as $month => $count) {
            if (isset($months[$month])) {
                $months[$month] = $count;
            }
        }

        // Format labels to readable month names
        $labels = array_map(function ($key) {
            return Carbon::parse($key . '-01')->translatedFormat('M Y');
        }, array_keys($months));

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pemohon',
                    'data' => array_values($months),
                    'borderColor' => 'rgba(255, 159, 64, 1)',
                    'backgroundColor' => 'rgba(255, 159, 64, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointBackgroundColor' => 'rgba(255, 159, 64, 1)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
