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

class ActivityTypeChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Berdasarkan Sifat Kegiatan';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('service_id')
                ->label('Layanan')
                ->options(Service::pluck('name', 'id'))
                ->placeholder('Semua Layanan')
                ->searchable(),
            Select::make('location_id')
                ->label('Lokasi')
                ->options(ConsultationLocation::pluck('name', 'id'))
                ->placeholder('Semua Lokasi')
                ->searchable(),
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

        if (!empty($this->filters['service_id'])) {
            $query->where('service_id', $this->filters['service_id']);
        }

        if (!empty($this->filters['location_id'])) {
            $query->where('consultation_location_id', $this->filters['location_id']);
        }

        $businessCount = (clone $query)->where('activity_type', 'business')->count();
        $nonBusinessCount = (clone $query)->where('activity_type', 'non_business')->count();
        $otherCount = (clone $query)
            ->where(function ($q) {
                $q->whereNotIn('activity_type', ['business', 'non_business'])
                  ->orWhereNull('activity_type');
            })
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pemohon',
                    'data' => [$nonBusinessCount, $businessCount, $otherCount],
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(153, 153, 153, 0.7)',
                    ],
                    'borderColor' => [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 153, 153, 1)',
                    ],
                    'borderWidth' => 2,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => ['Non Berusaha', 'Berusaha', 'Lainnya'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
