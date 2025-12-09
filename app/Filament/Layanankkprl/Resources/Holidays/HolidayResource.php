<?php

namespace App\Filament\Layanankkprl\Resources\Holidays;

use App\Filament\Layanankkprl\Resources\Holidays\Pages\CreateHoliday;
use App\Filament\Layanankkprl\Resources\Holidays\Pages\EditHoliday;
use App\Filament\Layanankkprl\Resources\Holidays\Pages\ListHolidays;
use App\Filament\Layanankkprl\Resources\Holidays\Schemas\HolidayForm;
use App\Filament\Layanankkprl\Resources\Holidays\Tables\HolidaysTable;
use App\Models\Holiday;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $recordTitleAttribute = 'description';

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'id' ? 'Hari Libur' : 'Holidays';
    }

    public static function getModelLabel(): string
    {
        return app()->getLocale() === 'id' ? 'Hari Libur' : 'Holiday';
    }

    public static function getPluralModelLabel(): string
    {
        return app()->getLocale() === 'id' ? 'Hari Libur' : 'Holidays';
    }

    public static function form(Schema $schema): Schema
    {
        return HolidayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HolidaysTable::configure($table);
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
            'index' => ListHolidays::route('/'),
            'create' => CreateHoliday::route('/create'),
            'edit' => EditHoliday::route('/{record}/edit'),
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
