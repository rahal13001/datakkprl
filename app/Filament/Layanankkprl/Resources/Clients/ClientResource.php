<?php

namespace App\Filament\Layanankkprl\Resources\Clients;

use App\Filament\Layanankkprl\Resources\Clients\Pages\CreateClient;
use App\Filament\Layanankkprl\Resources\Clients\Pages\EditClient;
use App\Filament\Layanankkprl\Resources\Clients\Pages\ListClients;
use App\Filament\Layanankkprl\Resources\Clients\Pages\ViewClient;
use App\Filament\Layanankkprl\Resources\Clients\Schemas\ClientForm;
use App\Filament\Layanankkprl\Resources\Clients\Schemas\ClientInfolist;
use App\Filament\Layanankkprl\Resources\Clients\Tables\ClientsTable;
use App\Models\Client;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'ticket_number';

    protected static ?string $recordRouteKeyName = 'ticket_number';

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Layanankkprl\Resources\Clients\ClientResource\RelationManagers\AssignmentsRelationManager::class,
            \App\Filament\Layanankkprl\Resources\Clients\ClientResource\RelationManagers\ConsultationReportsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
            'edit' => EditClient::route('/{record}/edit'),
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
