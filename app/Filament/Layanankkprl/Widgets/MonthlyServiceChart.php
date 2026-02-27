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
        $start = Carbon::parse($this->filters['start_date'] ?? Carbon::now()->startOfYear()->toDateString())->startOfDay();
        $end = Carbon::parse($this->filters['end_date'] ?? Carbon::now()->toDateString())->endOfDay();

        // Build month labels and data between start and end
        $months = [];
        $current = $start->copy()->startOfMonth();
        while ($current->lte($end)) {
            $months[$current->format('Y-m')] = 0;
            $current->addMonth();
        }

        // Build query with filters
        $query = Client::whereBetween('created_at', [$start, $end]);

        if (!empty($this->filters['service_id'])) {
            $query->where('service_id', $this->filters['service_id']);
        }

        if (!empty($this->filters['location_id'])) {
            $query->where('consultation_location_id', $this->filters['location_id']);
        }

        if (!empty($this->filters['activity_type'])) {
            $query->where('activity_type', $this->filters['activity_type']);
        }

        // Query monthly counts
        $results = $query
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total')
            )
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
