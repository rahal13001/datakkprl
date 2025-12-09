<?php

namespace App\Filament\Layanankkprl\Resources\Services;

use App\Filament\Layanankkprl\Resources\Services\Pages\CreateService;
use App\Filament\Layanankkprl\Resources\Services\Pages\EditService;
use App\Filament\Layanankkprl\Resources\Services\Pages\ListServices;
use App\Filament\Layanankkprl\Resources\Services\Schemas\ServiceForm;
use App\Filament\Layanankkprl\Resources\Services\Tables\ServicesTable;
use App\Models\Service;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'Layanan';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $recordRouteKeyName = 'slug';

    public static function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicesTable::configure($table);
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
            'index' => ListServices::route('/'),
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
