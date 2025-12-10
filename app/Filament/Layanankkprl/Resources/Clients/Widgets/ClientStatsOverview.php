<?php

namespace App\Filament\Layanankkprl\Resources\Clients\Widgets;

use App\Models\Client;
use App\Models\Schedule;
use App\Models\Service;
use Filament\Tables\Contracts\HasTable;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use LogicException;

use function Livewire\trigger;

class ClientStatsOverview extends BaseWidget
{
    // --- Inlined from InteractsWithPageTable to fix property typing ---

    /** @var array<string, int> */
    #[Reactive]
    public $paginators = [];

    /**
     * @var array<string, string | array<string, string | null> | null>
     */
    #[Reactive]
    public $tableColumnSearches = [];

    #[Reactive]
    public $tableGrouping = null;

    /**
     * @var array<string, mixed> | null
     */
    #[Reactive]
    public $tableFilters = null;

    #[Reactive]
    public $tableRecordsPerPage = null;

    /**
     * @var ?string
     */
    #[Reactive]
    public $tableSearch = '';

    #[Reactive]
    public $tableSort = null;

    #[Reactive]
    public $activeTab = null;

    #[Reactive] #[Locked]
    public $parentRecord = null;

    protected HasTable $tablePage;

    protected function getTablePageMountParameters(): array
    {
        return [];
    }

    protected function getTablePageInstance(): HasTable
    {
        if (! isset($this->tablePage)) {
            $this->tablePage = app('livewire')->new($this->getTablePage());
            trigger('mount', $this->tablePage, [], null, null);
        }

        $page = $this->tablePage;

        foreach ([
            'activeTab' => $this->activeTab,
            'paginators' => $this->paginators,
            'parentRecord' => $this->parentRecord,
            'tableColumnSearches' => $this->tableColumnSearches ?? [],
            'tableFilters' => $this->tableFilters ?? [],
            'tableGrouping' => $this->tableGrouping,
            'tableRecordsPerPage' => $this->tableRecordsPerPage,
            'tableSearch' => $this->tableSearch,
            'tableSort' => $this->tableSort,
            ...$this->getTablePageMountParameters(),
        ] as $property => $value) {
            $page->{$property} = $value;
        }

        $page->bootedInteractsWithTable();

        return $page;
    }

    protected function getPageTableQuery(): Builder
    {
        return $this->getTablePageInstance()->getFilteredSortedTableQuery();
    }

    protected function getPageTableRecords(): Collection | Paginator
    {
        return $this->getTablePageInstance()->getTableRecords();
    }

    // --- End Inlined Logic ---

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
