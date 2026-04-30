<?php

namespace App\Filament\Layanankkprl\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class StaffScoreSummaryTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 20;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Rekap Skor Petugas')
            ->description('Diurutkan berdasarkan rata-rata skor tertinggi pada rentang tanggal terpilih.')
            ->query($this->getTableQuery())
            ->defaultSort('average_score', 'desc')
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
                    ->query(function (Builder $query, array $data): Builder {
                        $from = filled($data['from'] ?? null)
                            ? Carbon::parse($data['from'])->startOfDay()->toDateString()
                            : null;
                        $until = filled($data['until'] ?? null)
                            ? Carbon::parse($data['until'])->endOfDay()->toDateString()
                            : null;

                        return $query
                            ->when(
                                $from,
                                fn (Builder $query) => $query->whereDate('schedules.date', '>=', $from),
                            )
                            ->when(
                                $until,
                                fn (Builder $query) => $query->whereDate('schedules.date', '<=', $until),
                            );
                    })
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
        return User::query()
            ->select('users.id', 'users.name', 'users.jabatan')
            ->join('assignments', 'assignments.user_id', '=', 'users.id')
            ->join('schedules', 'schedules.id', '=', 'assignments.schedule_id')
            ->whereNull('assignments.deleted_at')
            ->whereNull('schedules.deleted_at')
            ->whereNotNull('assignments.score')
            ->groupBy('users.id', 'users.name', 'users.jabatan')
            ->selectRaw('COUNT(assignments.id) as rated_sessions')
            ->selectRaw('SUM(assignments.score) as total_score')
            ->selectRaw('ROUND(AVG(assignments.score), 2) as average_score')
            ->selectRaw('ROUND(AVG(assignments.score) / 2, 2) as average_stars')
            ->selectRaw('MAX(assignments.score) as highest_score')
            ->orderByDesc('average_score')
            ->orderByDesc('rated_sessions');
    }
}
