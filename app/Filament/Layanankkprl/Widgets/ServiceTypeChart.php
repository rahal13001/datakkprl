<?php

namespace App\Filament\Layanankkprl\Widgets;

use App\Models\Client;
use App\Models\ConsultationLocation;
use App\Models\Service;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
            Select::make('location_id')
                ->label('Lokasi')
                ->options(ConsultationLocation::pluck('name', 'id'))
                ->placeholder('Semua Lokasi')
                ->searchable(),
            Select::make('activity_type')
                ->label('Sifat')
                ->options([
                    'non_business' => 'Non Berusaha',
                    'business' => 'Berusaha',
                ])
                ->placeholder('Semua Sifat'),
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

        $query = Client::whereBetween('created_at', [
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->endOfDay(),
        ]);

        if (!empty($this->filters['location_id'])) {
            $query->where('consultation_location_id', $this->filters['location_id']);
        }

        if (!empty($this->filters['activity_type'])) {
            $query->where('activity_type', $this->filters['activity_type']);
        }

        $services = Service::all();

        $countsByService = (clone $query)
            ->selectRaw('service_id, COUNT(*) as count')
            ->groupBy('service_id')
            ->pluck('count', 'service_id')
            ->toArray();

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
                    'data' => $services->map(fn($s) => $countsByService[$s->id] ?? 0)->toArray(),
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
