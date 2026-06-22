<?php

namespace App\Filament\Layanankkprl\Widgets;

use App\Models\User;
use App\Services\StaffPerformanceService;
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
    protected int|string|array $columnSpan = 'full';

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
                            return 'Periode layanan: '.
                                Carbon::parse($from)->translatedFormat('j M Y').
                                ' - '.
                                Carbon::parse($until)->translatedFormat('j M Y');
                        }

                        if (filled($from)) {
                            return 'Periode layanan dari '.Carbon::parse($from)->translatedFormat('j M Y');
                        }

                        return 'Periode layanan sampai '.Carbon::parse($until)->translatedFormat('j M Y');
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
                            ->modalHeading(fn (User $record): string => 'Rincian Aktivitas Layanan: '.$record->name)
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
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' / 5')
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
        return app(StaffPerformanceService::class)->summaryQuery(
            $this->getDateFilterValue('from'),
            $this->getDateFilterValue('until'),
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
            return 'Periode layanan: '.Carbon::parse($from)->translatedFormat('j M Y').' - '.Carbon::parse($until)->translatedFormat('j M Y');
        }

        if (filled($from)) {
            return 'Periode layanan dari '.Carbon::parse($from)->translatedFormat('j M Y');
        }

        if (filled($until)) {
            return 'Periode layanan sampai '.Carbon::parse($until)->translatedFormat('j M Y');
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
        return app(StaffPerformanceService::class)->serviceBreakdown(
            $user,
            $this->getDateFilterValue('from'),
            $this->getDateFilterValue('until'),
        );
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

        return $nonZeroBreakdown->implode(' | ').' | Klik untuk detail';
    }
}
