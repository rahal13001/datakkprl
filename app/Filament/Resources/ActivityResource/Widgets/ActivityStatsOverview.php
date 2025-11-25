<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Models\Activity;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use LogicException;
use function Livewire\trigger;

class ActivityStatsOverview extends BaseWidget
{
    /**
     * NOTE: This widget inlines the logic from \Filament\Widgets\Concerns\InteractsWithPageTable
     * to allow $tableColumnSearches to be nullable (?array).
     * The original trait enforces a strict array type, which causes errors when resetting filters
     * (Livewire sends null).
     *
     * When upgrading Filament, check if this trait has changed or if the issue is resolved upstream.
     */

    // use InteractsWithPageTable; // Removed to fix type error

    /** @var array<string, int> */
    #[Reactive]
    public $paginators = [];

    /**
     * @var array<string, string | array<string, string | null> | null>
     */
    #[Reactive]
    public ?array $tableColumnSearches = []; // Made nullable

    #[Reactive]
    public ?string $tableGrouping = null;

    /**
     * @var array<string, mixed> | null
     */
    #[Reactive]
    public ?array $tableFilters = null;

    #[Reactive]
    public int | string | null $tableRecordsPerPage = null;

    /**
     * @var ?string
     */
    #[Reactive]
    public $tableSearch = '';

    #[Reactive]
    public ?string $tableSort = null;

    #[Reactive]
    public ?string $activeTab = null;

    #[Reactive] #[Locked]
    public ?Model $parentRecord = null;

    protected HasTable $tablePage;

    protected function getTablePage(): string
    {
        return \App\Filament\Resources\ActivityResource\Pages\ListActivities::class;
    }

    /**
     * @return array<string, mixed>
     */
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

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pentek', $this->getPageTableQuery()->distinct('activity_code')->count('activity_code')),
            Stat::make('Total Pentek (UPT)', $this->getPageTableQuery()->where('organizer', 'UPT')->distinct('activity_code')->count('activity_code')),
            Stat::make('Total Pentek (Pusat)', $this->getPageTableQuery()->where('organizer', 'Pusat')->distinct('activity_code')->count('activity_code')),
        ];
    }
}
