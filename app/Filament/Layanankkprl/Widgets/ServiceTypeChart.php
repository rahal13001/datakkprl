<?php

namespace App\Filament\Layanankkprl\Widgets;

use App\Models\Client;
use App\Models\Service;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;

class ServiceTypeChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Berdasarkan Jenis Layanan';

    protected static ?int $sort = 3;

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
        $start = $this->filters['start_date'] ?? Carbon::now()->startOfYear()->toDateString();
        $end = $this->filters['end_date'] ?? Carbon::now()->toDateString();

        $services = Service::withCount(['clients' => function ($query) use ($start, $end) {
            $query->whereBetween('clients.created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay(),
            ]);
        }])->get();

        $colors = [
            'rgba(255, 159, 64, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(46, 204, 113, 0.8)',
            'rgba(231, 76, 60, 0.8)',
        ];

        $borderColors = array_map(fn($c) => str_replace('0.8)', '1)', $c), $colors);

        return [
            'datasets' => [
                [
                    'data' => $services->pluck('clients_count')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $services->count()),
                    'borderColor' => array_slice($borderColors, 0, $services->count()),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $services->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 15,
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
