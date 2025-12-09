<?php

namespace App\Filament\Layanankkprl\Resources\Regulations;

use App\Filament\Layanankkprl\Resources\Regulations\Pages\CreateRegulation;
use App\Filament\Layanankkprl\Resources\Regulations\Pages\EditRegulation;
use App\Filament\Layanankkprl\Resources\Regulations\Pages\ListRegulations;
use App\Filament\Layanankkprl\Resources\Regulations\Schemas\RegulationForm;
use App\Filament\Layanankkprl\Resources\Regulations\Tables\RegulationsTable;
use App\Models\Regulation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RegulationResource extends Resource
{
    protected static ?string $model = Regulation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $recordRouteKeyName = 'slug';

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'id' ? 'Regulasi' : 'Regulations';
    }

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'id' ? 'Regulasi' : 'Regulation';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'id' ? 'Regulasi' : 'Regulations';
    }

    public static function form(Schema $schema): Schema
    {
        return RegulationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegulationsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return \App\Filament\Layanankkprl\Resources\Regulations\Schemas\RegulationInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegulations::route('/'),
            'create' => CreateRegulation::route('/create'),
            'edit' => EditRegulation::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
