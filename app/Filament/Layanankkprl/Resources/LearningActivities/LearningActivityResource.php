<?php

namespace App\Filament\Layanankkprl\Resources\LearningActivities;

use App\Filament\Layanankkprl\Resources\LearningActivities\Pages\ListLearningActivities;
use App\Http\Controllers\LearningTrackingController;
use App\Models\LearningActivityLog;
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

class LearningActivityResource extends Resource
{
    protected static ?string $model = LearningActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring Belajar KKPRL';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Aktivitas Belajar';
    }

    public static function getModelLabel(): string
    {
        return 'Aktivitas Belajar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Aktivitas Belajar';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('learning.detailed_tracking_enabled');
    }

    public static function canViewAny(): bool
    {
        return config('learning.detailed_tracking_enabled') && parent::canViewAny();
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('occurred_at', 'desc')->columns([
            TextColumn::make('occurred_at')->label('Waktu')->dateTime('d M Y H:i:s')->sortable(),
            TextColumn::make('access.name')->label('Nama')->searchable()->sortable(),
            TextColumn::make('access.institution')->label('Instansi')->searchable()->sortable(),
            TextColumn::make('access.group_title')->label('Grup Pembelajaran')->searchable()->sortable()->wrap(),
            TextColumn::make('material_title')->label('Materi')->searchable()->sortable()->wrap(),
            TextColumn::make('activity_type')->label('Aktivitas')->badge()->sortable(),
            TextColumn::make('progress_percent')->label('Progres')->suffix('%')->placeholder('-')->sortable(),
            TextColumn::make('page_url')->label('Halaman')->url(fn ($record) => $record->page_url, shouldOpenInNewTab: true)->limit(40)->toggleable(),
            TextColumn::make('metadata')->label('Metadata')->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : '-')->limit(50)->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Filter::make('occurred_at')->label('Rentang Tanggal')->schema([DatePicker::make('from')->label('Dari'), DatePicker::make('until')->label('Sampai')])
                ->query(fn (Builder $query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('occurred_at', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('occurred_at', '<=', $date))),
            SelectFilter::make('material_key')->label('Materi')->options(fn () => LearningActivityLog::query()->whereNotNull('material_key')->distinct()->orderBy('material_title')->pluck('material_title', 'material_key')->all())->searchable(),
            SelectFilter::make('activity_type')->label('Jenis Aktivitas')->options(array_combine(LearningTrackingController::ACTIVITY_TYPES, LearningTrackingController::ACTIVITY_TYPES)),
            SelectFilter::make('institution')->label('Instansi')->options(fn () => LearningMaterialAccess::query()->distinct()->orderBy('institution')->pluck('institution', 'institution')->all())
                ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($q, $value) => $q->whereHas('access', fn ($a) => $a->where('institution', $value)))),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLearningActivities::route('/')];
    }
}
