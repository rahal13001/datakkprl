<?php

namespace App\Filament\Layanankkprl\Resources\LearningAccesses;

use App\Filament\Layanankkprl\Resources\LearningAccesses\Pages\ListLearningAccesses;
use App\Models\LearningMaterialAccess;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LearningAccessResource extends Resource
{
    protected static ?string $model = LearningMaterialAccess::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring Belajar KKPRL';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Akses Materi';
    }

    public static function getModelLabel(): string
    {
        return 'Akses Materi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Akses Materi';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('activities')->withMax('activities', 'progress_percent')->withMax('activities', 'occurred_at'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('institution')->label('Asal Instansi')->searchable()->sortable()->wrap(),
                TextColumn::make('access_purpose')->label('Tujuan Mengakses Materi')->searchable()->wrap()->limit(60),
                TextColumn::make('material_title')->label('Materi')->searchable()->sortable()->wrap(),
                TextColumn::make('material_key')->label('Kunci Materi')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('first_seen_at')->label('Akses Pertama')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('last_seen_at')->label('Akses Terakhir')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('visit_count')->label('Kunjungan')->numeric()->sortable(),
                TextColumn::make('activities_count')->label('Aktivitas')->numeric()->sortable(),
                TextColumn::make('activities_max_progress_percent')->label('Progres Tertinggi')->suffix('%')->placeholder('-')->sortable(),
                TextColumn::make('activities_max_occurred_at')->label('Aktivitas Terakhir')->dateTime('d M Y H:i')->placeholder('-')->sortable(),
            ])
            ->filters([
                Filter::make('created_at')->label('Rentang Tanggal')->schema([DatePicker::make('from')->label('Dari'), DatePicker::make('until')->label('Sampai')])
                    ->query(fn (Builder $query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),
                SelectFilter::make('institution')->label('Instansi')->options(fn () => LearningMaterialAccess::query()->distinct()->orderBy('institution')->pluck('institution', 'institution')->all())->searchable(),
                SelectFilter::make('material_key')->label('Materi')->options(fn () => LearningMaterialAccess::query()->distinct()->orderBy('material_title')->pluck('material_title', 'material_key')->all())->searchable(),
                SelectFilter::make('access_purpose')->label('Tujuan')->options(fn () => LearningMaterialAccess::query()->distinct()->orderBy('access_purpose')->pluck('access_purpose', 'access_purpose')->all())->searchable(),
                SelectFilter::make('progress')->label('Progres Minimum')->options([25 => '25%', 50 => '50%', 75 => '75%', 100 => '100%'])
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($q, $progress) => $q->whereHas('activities', fn ($a) => $a->where('progress_percent', '>=', $progress)))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLearningAccesses::route('/')];
    }
}
