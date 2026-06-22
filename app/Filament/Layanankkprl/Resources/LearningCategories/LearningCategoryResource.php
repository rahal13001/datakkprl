<?php

namespace App\Filament\Layanankkprl\Resources\LearningCategories;

use App\Filament\Layanankkprl\Resources\LearningCategories\Pages\CreateLearningCategory;
use App\Filament\Layanankkprl\Resources\LearningCategories\Pages\EditLearningCategory;
use App\Filament\Layanankkprl\Resources\LearningCategories\Pages\ListLearningCategories;
use App\Filament\Layanankkprl\Resources\LearningCategories\Schemas\LearningCategoryForm;
use App\Filament\Layanankkprl\Resources\LearningCategories\Schemas\LearningCategoryInfolist;
use App\Filament\Layanankkprl\Resources\LearningCategories\Tables\LearningCategoriesTable;
use App\Models\LearningCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LearningCategoryResource extends Resource
{
    protected static ?string $model = LearningCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|UnitEnum|null $navigationGroup = 'Belajar KKPRL';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $recordRouteKeyName = 'slug';

    public static function getNavigationLabel(): string
    {
        return 'Kategori Pembelajaran';
    }

    public static function getModelLabel(): string
    {
        return 'Kategori Pembelajaran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kategori Pembelajaran';
    }

    public static function form(Schema $schema): Schema
    {
        return LearningCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningCategoriesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LearningCategoryInfolist::configure($schema);
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
            'index' => ListLearningCategories::route('/'),
            'create' => CreateLearningCategory::route('/create'),
            'edit' => EditLearningCategory::route('/{record}/edit'),
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
