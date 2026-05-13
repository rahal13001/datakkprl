<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\ConsultationLocation;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PublicDashboardCharts extends Component
{
    public array $serviceTypeFilters = [];

    public array $serviceLocationFilters = [];

    public array $activityTypeFilters = [];

    public array $monthlyFilters = [];

    public function mount(): void
    {
        $defaultStart = Carbon::now()->startOfYear()->toDateString();
        $defaultEnd = Carbon::now()->toDateString();

        $this->serviceTypeFilters = [
            'location_id' => null,
            'activity_type' => null,
            'start_date' => $defaultStart,
            'end_date' => $defaultEnd,
        ];

        $this->serviceLocationFilters = [
            'service_id' => null,
            'activity_type' => null,
            'start_date' => $defaultStart,
            'end_date' => $defaultEnd,
        ];

        $this->activityTypeFilters = [
            'service_id' => null,
            'location_id' => null,
            'start_date' => $defaultStart,
            'end_date' => $defaultEnd,
        ];

        $this->monthlyFilters = [
            'service_id' => null,
            'location_id' => null,
            'activity_type' => null,
            'start_date' => $defaultStart,
            'end_date' => $defaultEnd,
        ];
    }

    protected function normalizeDateRange(array $filters): array
    {
        $start = filled($filters['start_date'] ?? null)
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : Carbon::now()->startOfYear()->startOfDay();

        $end = filled($filters['end_date'] ?? null)
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : Carbon::now()->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    protected function baseQuery(array $filters): Builder
    {
        [$start, $end] = $this->normalizeDateRange($filters);

        return Client::query()->whereHas('schedules', function (Builder $builder) use ($start, $end) {
            $builder->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
        });
    }

    protected function chartPalette(int $count): array
    {
        $background = [
            'rgba(0, 87, 255, 0.82)',
            'rgba(0, 194, 255, 0.82)',
            'rgba(30, 41, 59, 0.82)',
            'rgba(59, 130, 246, 0.82)',
            'rgba(14, 116, 144, 0.82)',
            'rgba(71, 85, 105, 0.82)',
            'rgba(96, 165, 250, 0.82)',
            'rgba(56, 189, 248, 0.82)',
        ];

        $border = [
            'rgba(0, 87, 255, 1)',
            'rgba(0, 194, 255, 1)',
            'rgba(30, 41, 59, 1)',
            'rgba(59, 130, 246, 1)',
            'rgba(14, 116, 144, 1)',
            'rgba(71, 85, 105, 1)',
            'rgba(96, 165, 250, 1)',
            'rgba(56, 189, 248, 1)',
        ];

        return [
            'background' => array_slice($background, 0, $count),
            'border' => array_slice($border, 0, $count),
        ];
    }

    public function getServiceTypeChartConfigProperty(): array
    {
        $query = $this->baseQuery($this->serviceTypeFilters);

        if (!empty($this->serviceTypeFilters['location_id'])) {
            $query->where('consultation_location_id', $this->serviceTypeFilters['location_id']);
        }

        if (!empty($this->serviceTypeFilters['activity_type'])) {
            $query->where('activity_type', $this->serviceTypeFilters['activity_type']);
        }

        $services = Service::all(['id', 'name']);
        $countsByService = (clone $query)
            ->selectRaw('service_id, COUNT(*) as count')
            ->groupBy('service_id')
            ->pluck('count', 'service_id')
            ->toArray();

        $palette = $this->chartPalette($services->count());

        return [
            'type' => 'doughnut',
            'data' => [
                'datasets' => [
                    [
                        'data' => $services->map(fn ($service) => $countsByService[$service->id] ?? 0)->toArray(),
                        'backgroundColor' => $palette['background'],
                        'borderColor' => $palette['border'],
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => $services->pluck('name')->toArray(),
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
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
            ],
        ];
    }

    public function getServiceLocationChartConfigProperty(): array
    {
        $query = $this->baseQuery($this->serviceLocationFilters);

        if (!empty($this->serviceLocationFilters['service_id'])) {
            $query->where('service_id', $this->serviceLocationFilters['service_id']);
        }

        if (!empty($this->serviceLocationFilters['activity_type'])) {
            $query->where('activity_type', $this->serviceLocationFilters['activity_type']);
        }

        $locations = ConsultationLocation::all(['id', 'name']);
        $countsByLocation = (clone $query)
            ->selectRaw('consultation_location_id, COUNT(*) as count')
            ->groupBy('consultation_location_id')
            ->pluck('count', 'consultation_location_id')
            ->toArray();

        $palette = $this->chartPalette($locations->count());

        return [
            'type' => 'doughnut',
            'data' => [
                'datasets' => [
                    [
                        'data' => $locations->map(fn ($location) => $countsByLocation[$location->id] ?? 0)->toArray(),
                        'backgroundColor' => $palette['background'],
                        'borderColor' => $palette['border'],
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => $locations->pluck('name')->toArray(),
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
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
            ],
        ];
    }

    public function getActivityTypeChartConfigProperty(): array
    {
        $query = $this->baseQuery($this->activityTypeFilters);

        if (!empty($this->activityTypeFilters['service_id'])) {
            $query->where('service_id', $this->activityTypeFilters['service_id']);
        }

        if (!empty($this->activityTypeFilters['location_id'])) {
            $query->where('consultation_location_id', $this->activityTypeFilters['location_id']);
        }

        $businessCount = (clone $query)->where('activity_type', 'business')->count();
        $nonBusinessCount = (clone $query)->where('activity_type', 'non_business')->count();
        return [
            'type' => 'bar',
            'data' => [
                'datasets' => [
                    [
                        'label' => 'Jumlah Pemohon',
                        'data' => [$nonBusinessCount, $businessCount],
                        'backgroundColor' => [
                            'rgba(0, 194, 255, 0.82)',
                            'rgba(0, 87, 255, 0.82)',
                        ],
                        'borderColor' => [
                            'rgba(0, 194, 255, 1)',
                            'rgba(0, 87, 255, 1)',
                        ],
                        'borderWidth' => 2,
                        'borderRadius' => 6,
                    ],
                ],
                'labels' => ['Non Berusaha', 'Berusaha'],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
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
            ],
        ];
    }

    public function getMonthlyServiceChartConfigProperty(): array
    {
        [$start, $end] = $this->normalizeDateRange($this->monthlyFilters);
        $months = [];
        $current = $start->copy()->startOfMonth();

        while ($current->lte($end)) {
            $months[$current->format('Y-m')] = 0;
            $current->addMonth();
        }

        $query = $this->baseQuery($this->monthlyFilters);

        if (!empty($this->monthlyFilters['service_id'])) {
            $query->where('service_id', $this->monthlyFilters['service_id']);
        }

        if (!empty($this->monthlyFilters['location_id'])) {
            $query->where('consultation_location_id', $this->monthlyFilters['location_id']);
        }

        if (!empty($this->monthlyFilters['activity_type'])) {
            $query->where('activity_type', $this->monthlyFilters['activity_type']);
        }

        $earliestDateSubquery = Schedule::select('date')
            ->whereColumn('schedules.client_id', 'clients.id')
            ->orderBy('date', 'asc')
            ->limit(1);
        $earliestDateSql = $earliestDateSubquery->toRawSql();
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', ({$earliestDateSql}))"
            : "DATE_FORMAT(({$earliestDateSql}), '%Y-%m')";

        $results = $query
            ->select(
                DB::raw("{$monthExpression} as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        foreach ($results as $month => $count) {
            if (isset($months[$month])) {
                $months[$month] = $count;
            }
        }

        $labels = array_map(fn ($key) => Carbon::parse($key . '-01')->translatedFormat('M Y'), array_keys($months));

        return [
            'type' => 'line',
            'data' => [
                'datasets' => [
                    [
                        'label' => 'Jumlah Pemohon',
                        'data' => array_values($months),
                        'borderColor' => 'rgba(0, 87, 255, 1)',
                        'backgroundColor' => 'rgba(0, 194, 255, 0.16)',
                        'fill' => true,
                        'tension' => 0.3,
                        'pointBackgroundColor' => 'rgba(0, 87, 255, 1)',
                        'pointBorderColor' => '#fff',
                        'pointBorderWidth' => 2,
                        'pointRadius' => 5,
                        'pointHoverRadius' => 7,
                    ],
                ],
                'labels' => $labels,
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
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
            ],
        ];
    }

    public function render()
    {
        return view('livewire.public-dashboard-charts', [
            'services' => Service::query()->orderBy('name')->get(['id', 'name']),
            'locations' => ConsultationLocation::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
