<?php

namespace App\Filament\Layanankkprl\Resources\LearningSessions;

use App\Filament\Layanankkprl\Resources\LearningSessions\Pages\ListLearningSessions;
use App\Models\LearningMaterialAccess;
use App\Models\LearningSession;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LearningSessionResource extends Resource
{
    protected static ?string $model = LearningSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring Belajar KKPRL';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Sesi Belajar';
    }

    public static function getModelLabel(): string
    {
        return 'Sesi Belajar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Sesi Belajar';
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
        return $table->modifyQueryUsing(fn (Builder $query) => $query->withCount('activities'))->defaultSort('started_at', 'desc')->columns([
            TextColumn::make('access.name')->label('Nama')->searchable()->sortable(),
            TextColumn::make('access.institution')->label('Instansi')->searchable()->sortable(),
            TextColumn::make('access.group_title')->label('Grup Pembelajaran')->searchable()->sortable()->wrap(),
            TextColumn::make('started_at')->label('Mulai')->dateTime('d M Y H:i:s')->sortable(),
            TextColumn::make('ended_at')->label('Selesai')->dateTime('d M Y H:i:s')->placeholder('Aktif / tidak tercatat')->sortable(),
            TextColumn::make('duration_seconds')->label('Durasi')->formatStateUsing(fn (?int $state) => $state === null ? '-' : gmdate('H:i:s', $state))->sortable(),
            TextColumn::make('activities_count')->label('Aktivitas')->numeric()->sortable(),
        ])->filters([
            Filter::make('started_at')->label('Rentang Tanggal')->schema([DatePicker::make('from')->label('Dari'), DatePicker::make('until')->label('Sampai')])
                ->query(fn (Builder $query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('started_at', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('started_at', '<=', $date))),
            SelectFilter::make('group')->label('Grup')->options(fn () => LearningMaterialAccess::query()->whereNotNull('group_key')->distinct()->orderBy('group_title')->pluck('group_title', 'group_key')->all())
                ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($q, $key) => $q->whereHas('access', fn ($a) => $a->where('group_key', $key)))),
            SelectFilter::make('institution')->label('Instansi')->options(fn () => LearningMaterialAccess::query()->distinct()->orderBy('institution')->pluck('institution', 'institution')->all())
                ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($q, $value) => $q->whereHas('access', fn ($a) => $a->where('institution', $value)))),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLearningSessions::route('/')];
    }
}
