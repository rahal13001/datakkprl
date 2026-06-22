<?php

namespace App\Filament\Layanankkprl\Resources\LearningGroups;

use App\Filament\Layanankkprl\Resources\LearningGroups\Pages\CreateLearningGroup;
use App\Filament\Layanankkprl\Resources\LearningGroups\Pages\EditLearningGroup;
use App\Filament\Layanankkprl\Resources\LearningGroups\Pages\ListLearningGroups;
use App\Filament\Layanankkprl\Resources\LearningGroups\Schemas\LearningGroupForm;
use App\Filament\Layanankkprl\Resources\LearningGroups\Schemas\LearningGroupInfolist;
use App\Filament\Layanankkprl\Resources\LearningGroups\Tables\LearningGroupsTable;
use App\Models\LearningGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LearningGroupResource extends Resource
{
    protected static ?string $model = LearningGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Belajar KKPRL';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $recordRouteKeyName = 'slug';

    public static function getNavigationLabel(): string
    {
        return 'Grup Pembelajaran';
    }

    public static function getModelLabel(): string
    {
        return 'Grup Pembelajaran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Grup Pembelajaran';
    }

    public static function form(Schema $schema): Schema
    {
        return LearningGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningGroupsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LearningGroupInfolist::configure($schema);
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
            'index' => ListLearningGroups::route('/'),
            'create' => CreateLearningGroup::route('/create'),
            'edit' => EditLearningGroup::route('/{record}/edit'),
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
