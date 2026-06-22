<?php

namespace App\Filament\Layanankkprl\Resources\ServicePerformanceResults;

use App\Filament\Layanankkprl\Resources\ServicePerformanceResults\Pages\CreateServicePerformanceResult;
use App\Filament\Layanankkprl\Resources\ServicePerformanceResults\Pages\EditServicePerformanceResult;
use App\Filament\Layanankkprl\Resources\ServicePerformanceResults\Pages\ListServicePerformanceResults;
use App\Filament\Layanankkprl\Resources\ServicePerformanceResults\Schemas\ServicePerformanceResultForm;
use App\Filament\Layanankkprl\Resources\ServicePerformanceResults\Tables\ServicePerformanceResultsTable;
use App\Models\ServicePerformanceResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ServicePerformanceResultResource extends Resource
{
    protected static ?string $model = ServicePerformanceResult::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return 'Hasil Kinerja';
    }

    public static function getModelLabel(): string
    {
        return 'Hasil Kinerja';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Hasil Kinerja';
    }

    public static function form(Schema $schema): Schema
    {
        return ServicePerformanceResultForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicePerformanceResultsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServicePerformanceResults::route('/'),
            'create' => CreateServicePerformanceResult::route('/create'),
            'edit' => EditServicePerformanceResult::route('/{record}/edit'),
        ];
    }
}
