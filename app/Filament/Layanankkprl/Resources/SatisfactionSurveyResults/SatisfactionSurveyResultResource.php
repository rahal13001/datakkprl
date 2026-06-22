<?php

namespace App\Filament\Layanankkprl\Resources\SatisfactionSurveyResults;

use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResults\Pages\CreateSatisfactionSurveyResult;
use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResults\Pages\EditSatisfactionSurveyResult;
use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResults\Pages\ListSatisfactionSurveyResults;
use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResults\Schemas\SatisfactionSurveyResultForm;
use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResults\Tables\SatisfactionSurveyResultsTable;
use App\Models\SatisfactionSurveyResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class SatisfactionSurveyResultResource extends Resource
{
    protected static ?string $model = SatisfactionSurveyResult::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return 'Hasil SKM';
    }

    public static function getModelLabel(): string
    {
        return 'Hasil SKM';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Hasil SKM';
    }

    public static function form(Schema $schema): Schema
    {
        return SatisfactionSurveyResultForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SatisfactionSurveyResultsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSatisfactionSurveyResults::route('/'),
            'create' => CreateSatisfactionSurveyResult::route('/create'),
            'edit' => EditSatisfactionSurveyResult::route('/{record}/edit'),
        ];
    }
}
