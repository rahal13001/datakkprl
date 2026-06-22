<?php

namespace App\Filament\Layanankkprl\Resources\LearningMaterials;

use App\Filament\Layanankkprl\Resources\LearningMaterials\Pages\CreateLearningMaterial;
use App\Filament\Layanankkprl\Resources\LearningMaterials\Pages\EditLearningMaterial;
use App\Filament\Layanankkprl\Resources\LearningMaterials\Pages\ListLearningMaterials;
use App\Filament\Layanankkprl\Resources\LearningMaterials\Schemas\LearningMaterialForm;
use App\Filament\Layanankkprl\Resources\LearningMaterials\Schemas\LearningMaterialInfolist;
use App\Filament\Layanankkprl\Resources\LearningMaterials\Tables\LearningMaterialsTable;
use App\Models\LearningMaterial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LearningMaterialResource extends Resource
{
    protected static ?string $model = LearningMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Belajar KKPRL';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $recordRouteKeyName = 'slug';

    public static function getNavigationLabel(): string
    {
        return 'Materi Pembelajaran';
    }

    public static function getModelLabel(): string
    {
        return 'Materi Pembelajaran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Materi Pembelajaran';
    }

    public static function form(Schema $schema): Schema
    {
        return LearningMaterialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningMaterialsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LearningMaterialInfolist::configure($schema);
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
            'index' => ListLearningMaterials::route('/'),
            'create' => CreateLearningMaterial::route('/create'),
            'edit' => EditLearningMaterial::route('/{record}/edit'),
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
