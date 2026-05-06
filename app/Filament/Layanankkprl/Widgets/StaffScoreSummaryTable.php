<?php

namespace App\Filament\Layanankkprl\Widgets;

use App\Models\Assignment;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StaffScoreSummaryTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 20;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Rekap Skor Petugas')
            ->description('Diurutkan berdasarkan jumlah pelayanan terbanyak pada rentang tanggal terpilih. Arahkan kursor atau klik jumlah layanan untuk melihat rinciannya.')
            ->query($this->getTableQuery())
            ->defaultSort('service_activities_count', 'desc')
            ->filters([
                Filter::make('service_date')
                    ->label('Tanggal Layanan')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfYear()->toDateString())
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->default(now()->endOfYear()->toDateString())
                            ->native(false),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if (blank($from) && blank($until)) {
                            return null;
                        }

                        if (filled($from) && filled($until)) {
                            return 'Periode layanan: ' .
                                Carbon::parse($from)->translatedFormat('j M Y') .
                                ' - ' .
                                Carbon::parse($until)->translatedFormat('j M Y');
                        }

                        if (filled($from)) {
                            return 'Periode layanan dari ' . Carbon::parse($from)->translatedFormat('j M Y');
                        }

                        return 'Periode layanan sampai ' . Carbon::parse($until)->translatedFormat('j M Y');
                    }),
            ])
            ->filtersFormColumns(2)
            ->deferFilters(false)
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('rated_sessions')
                    ->label('Jumlah Penilaian')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('service_activities_count')
                    ->label('Aktivitas Layanan')
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->tooltip(fn (User $record): string => $this->getServiceBreakdownTooltip($record))
                    ->action(
                        Action::make('viewServiceBreakdown')
                            ->label('Rincian Aktivitas Layanan')
                            ->modalHeading(fn (User $record): string => 'Rincian Aktivitas Layanan: ' . $record->name)
                            ->modalDescription(fn (): string => $this->getSelectedPeriodLabel())
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalWidth('lg')
                            ->modalContent(fn (User $record): View => view(
                                'filament.layanankkprl.widgets.staff-service-breakdown',
                                [
                                    'staff' => $record,
                                    'breakdown' => $this->getServiceBreakdown($record),
                                    'totalServiceActivities' => (int) ($record->service_activities_count ?? 0),
                                ],
                            )),
                    )
                    ->extraAttributes([
                        'class' => 'cursor-pointer',
                    ]),
                TextColumn::make('total_score')
                    ->label('Total Skor')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('average_score')
                    ->label('Rata-rata Skor')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('average_stars')
                    ->label('Rata-rata Bintang')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' / 5')
                    ->sortable(),
                TextColumn::make('highest_score')
                    ->label('Skor Tertinggi')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function getTableQuery(): Builder
    {
        $ratedAssignmentsQuery = $this->applyDateRange(
            Assignment::query()
                ->join('schedules', 'schedules.id', '=', 'assignments.schedule_id')
                ->whereColumn('assignments.user_id', 'users.id')
                ->whereNull('assignments.deleted_at')
                ->whereNull('schedules.deleted_at')
                ->whereNotNull('assignments.score')
        );

        $serviceActivitiesQuery = $this->applyDateRange(
            Client::query()
                ->join('schedules', 'schedules.client_id', '=', 'clients.id')
                ->join('assignments', 'assignments.schedule_id', '=', 'schedules.id')
                ->whereColumn('assignments.user_id', 'users.id')
                ->whereNull('clients.deleted_at')
                ->whereNull('schedules.deleted_at')
                ->whereNull('assignments.deleted_at')
        );

        return User::query()
            ->select('users.id', 'users.name', 'users.jabatan')
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'Pegawai'))
                    ->orWhereHas('assignments', fn (Builder $assignmentQuery) => $assignmentQuery->whereNull('assignments.deleted_at'));
            })
            ->selectSub(
                (clone $ratedAssignmentsQuery)->selectRaw('COUNT(assignments.id)'),
                'rated_sessions',
            )
            ->selectSub(
                (clone $serviceActivitiesQuery)->selectRaw('COUNT(DISTINCT clients.id)'),
                'service_activities_count',
            )
            ->selectSub(
                (clone $ratedAssignmentsQuery)->selectRaw('COALESCE(SUM(assignments.score), 0)'),
                'total_score',
            )
            ->selectSub(
                (clone $ratedAssignmentsQuery)->selectRaw('COALESCE(ROUND(AVG(assignments.score), 2), 0)'),
                'average_score',
            )
            ->selectSub(
                (clone $ratedAssignmentsQuery)->selectRaw('COALESCE(ROUND(AVG(assignments.score) / 2, 2), 0)'),
                'average_stars',
            )
            ->selectSub(
                (clone $ratedAssignmentsQuery)->selectRaw('COALESCE(MAX(assignments.score), 0)'),
                'highest_score',
            );
    }

    protected function applyDateRange(Builder $query): Builder
    {
        $from = $this->getDateFilterValue('from');
        $until = $this->getDateFilterValue('until');

        return $query
            ->when(
                $from,
                fn (Builder $query) => $query->whereDate('schedules.date', '>=', $from),
            )
            ->when(
                $until,
                fn (Builder $query) => $query->whereDate('schedules.date', '<=', $until),
            );
    }

    protected function getDateFilterValue(string $key): ?string
    {
        $filterState = $this->getResolvedServiceDateFilterState();
        $value = $filterState[$key] ?? null;

        if (blank($value)) {
            return null;
        }

        $date = Carbon::parse($value);

        if ($key === 'from') {
            $date->startOfDay();
        }

        if ($key === 'until') {
            $date->endOfDay();
        }

        return $date->toDateString();
    }

    protected function getSelectedPeriodLabel(): string
    {
        $filterState = $this->getResolvedServiceDateFilterState();
        $from = $filterState['from'] ?? null;
        $until = $filterState['until'] ?? null;

        if (filled($from) && filled($until)) {
            return 'Periode layanan: ' . Carbon::parse($from)->translatedFormat('j M Y') . ' - ' . Carbon::parse($until)->translatedFormat('j M Y');
        }

        if (filled($from)) {
            return 'Periode layanan dari ' . Carbon::parse($from)->translatedFormat('j M Y');
        }

        if (filled($until)) {
            return 'Periode layanan sampai ' . Carbon::parse($until)->translatedFormat('j M Y');
        }

        return 'Semua periode layanan';
    }

    protected function getResolvedServiceDateFilterState(): array
    {
        $filterState = $this->getTableFilterState('service_date');

        if (is_array($filterState)) {
            return $filterState;
        }

        return [
            'from' => now()->startOfYear()->toDateString(),
            'until' => now()->endOfYear()->toDateString(),
        ];
    }

    protected function getServiceBreakdown(User $user): Collection
    {
        $countsByService = $this->applyDateRange(
            Client::query()
                ->join('schedules', 'schedules.client_id', '=', 'clients.id')
                ->join('assignments', 'assignments.schedule_id', '=', 'schedules.id')
                ->where('assignments.user_id', $user->id)
                ->whereNull('clients.deleted_at')
                ->whereNull('schedules.deleted_at')
                ->whereNull('assignments.deleted_at')
        )
            ->selectRaw('clients.service_id, COUNT(DISTINCT clients.id) as total')
            ->groupBy('clients.service_id')
            ->pluck('total', 'clients.service_id');

        return Service::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Service $service): array => [
                'name' => $service->name,
                'count' => (int) ($countsByService[$service->id] ?? 0),
            ]);
    }

    protected function getServiceBreakdownTooltip(User $user): string
    {
        $breakdown = $this->getServiceBreakdown($user);

        $nonZeroBreakdown = $breakdown
            ->filter(fn (array $item): bool => $item['count'] > 0)
            ->map(fn (array $item): string => "{$item['name']}: {$item['count']}");

        if ($nonZeroBreakdown->isEmpty()) {
            return 'Belum ada aktivitas layanan pada periode ini.';
        }

        return $nonZeroBreakdown->implode(' | ') . ' | Klik untuk detail';
    }
}
