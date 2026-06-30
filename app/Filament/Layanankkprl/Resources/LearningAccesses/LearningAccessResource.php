<?php

namespace App\Filament\Layanankkprl\Resources\LearningAccesses;

use App\Filament\Layanankkprl\Resources\LearningAccesses\Pages\ListLearningAccesses;
use App\Models\LearningMaterialAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use UnitEnum;

class LearningAccessResource extends Resource
{
    protected static ?string $model = LearningMaterialAccess::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring Belajar KKPRL';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Akses Grup';
    }

    public static function getModelLabel(): string
    {
        return 'Akses Grup';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Akses Grup Pembelajaran';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount('materialOpens')
                ->when(
                    config('learning.detailed_tracking_enabled'),
                    fn (Builder $query) => $query->withCount('activities')->withMax('activities', 'progress_percent')->withMax('activities', 'occurred_at'),
                ))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('institution')->label('Asal Instansi')->searchable()->sortable()->wrap(),
                TextColumn::make('access_purpose')->label('Tujuan Mengakses Materi')->searchable()->wrap()->limit(60),
                TextColumn::make('group_title')->label('Grup Pembelajaran')->searchable()->sortable()->wrap(),
                TextColumn::make('group_key')->label('Kunci Grup')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('first_seen_at')->label('Akses Pertama')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('last_seen_at')->label('Akses Terakhir')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('visit_count')->label('Kunjungan')->numeric()->sortable(),
                TextColumn::make('materials_opened')
                    ->label('Materi Dibuka')
                    ->state(fn (LearningMaterialAccess $record): int => (int) ($record->material_opens_count ?? 0))
                    ->badge()
                    ->color('info')
                    ->tooltip('Klik untuk melihat materi yang telah dibuka')
                    ->action(
                        Action::make('viewOpenedMaterials')
                            ->label('Materi yang Dibuka')
                            ->modalHeading(fn (LearningMaterialAccess $record): string => 'Materi yang Dibuka oleh '.$record->name)
                            ->modalDescription(fn (LearningMaterialAccess $record): string => $record->group_title ?? 'Grup pembelajaran')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalWidth('lg')
                            ->modalContent(fn (LearningMaterialAccess $record): View => view(
                                'filament.layanankkprl.resources.learning-accesses.opened-materials',
                                [
                                    'access' => $record,
                                    'materials' => self::openedMaterials($record),
                                ],
                            )),
                    )
                    ->extraAttributes(['class' => 'cursor-pointer']),
                TextColumn::make('activities_count')->label('Aktivitas')->numeric()->sortable()->visible(fn (): bool => config('learning.detailed_tracking_enabled')),
                TextColumn::make('activities_max_progress_percent')->label('Progres Tertinggi')->suffix('%')->placeholder('-')->sortable()->visible(fn (): bool => config('learning.detailed_tracking_enabled')),
                TextColumn::make('activities_max_occurred_at')->label('Aktivitas Terakhir')->dateTime('d M Y H:i')->placeholder('-')->sortable()->visible(fn (): bool => config('learning.detailed_tracking_enabled')),
            ])
            ->filters([
                Filter::make('created_at')->label('Rentang Tanggal')->schema([DatePicker::make('from')->label('Dari'), DatePicker::make('until')->label('Sampai')])
                    ->query(fn (Builder $query, array $data) => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),
                SelectFilter::make('institution')->label('Instansi')->options(fn () => LearningMaterialAccess::query()->distinct()->orderBy('institution')->pluck('institution', 'institution')->all())->searchable(),
                SelectFilter::make('group_key')->label('Grup')->options(fn () => LearningMaterialAccess::query()->whereNotNull('group_key')->distinct()->orderBy('group_title')->pluck('group_title', 'group_key')->all())->searchable(),
                SelectFilter::make('access_purpose')->label('Tujuan')->options(fn () => LearningMaterialAccess::query()->distinct()->orderBy('access_purpose')->pluck('access_purpose', 'access_purpose')->all())->searchable(),
                SelectFilter::make('progress')->label('Progres Minimum')->options([25 => '25%', 50 => '50%', 75 => '75%', 100 => '100%'])
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($q, $progress) => $q->whereHas('activities', fn ($a) => $a->where('progress_percent', '>=', $progress))))
                    ->visible(fn (): bool => config('learning.detailed_tracking_enabled')),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLearningAccesses::route('/')];
    }

    public static function openedMaterials(LearningMaterialAccess $record): Collection
    {
        return $record->materialOpens()
            ->orderByDesc('last_opened_at')
            ->get();
    }
}
