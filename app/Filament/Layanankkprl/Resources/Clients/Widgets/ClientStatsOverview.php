<?php

namespace App\Filament\Layanankkprl\Resources\Clients\Widgets;

use App\Models\Client;
use App\Models\Schedule;
use App\Models\Service;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ClientStatsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return \App\Filament\Layanankkprl\Resources\Clients\Pages\ListClients::class;
    }

    protected function getStats(): array
    {
        // Get the current filtered query from the page
        $query = $this->getPageTableQuery();

        // 1. Total Clients (Filtered)
        $totalClients = $query->count();

        // 2. Total Schedules (for the filtered clients)
        $totalSchedules = (clone $query)->withCount('schedules')->get()->sum('schedules_count');

        // 3. Breakdown by Service
        $clientsByService = (clone $query)
            ->reorder() // Clear default table ordering to prevent SQL strict mode error
            ->select([]) // Clear inherited selects (e.g. subquery columns) to avoid strict mode error
            ->selectRaw('service_id, count(*) as count')
            ->groupBy('service_id')
            ->pluck('count', 'service_id')
            ->toArray();

        $stats = [
            Stat::make('Total Pemohon', $totalClients)
                ->description('Sesuai filter')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            
            Stat::make('Total Jadwal', $totalSchedules)
                ->description('Dari klien terpilih')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];

        // Ensure we load all active services to show even those with 0 count
        $services = Service::all();

        foreach ($services as $service) {
            $count = $clientsByService[$service->id] ?? 0;
            $stats[] = Stat::make($service->name, $count)
                ->description('Jumlah permohonan')
                ->color('success');
        }

        return $stats;
    }
}
