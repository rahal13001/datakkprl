<?php

namespace App\Filament\Layanankkprl\Resources\KkprlProposals;

use App\Filament\Layanankkprl\Resources\KkprlProposals\Pages\ListKkprlProposals;
use App\Filament\Layanankkprl\Resources\KkprlProposals\Pages\ViewKkprlProposal;
use App\Filament\Layanankkprl\Resources\KkprlProposals\RelationManagers\AttachmentsRelationManager;
use App\Filament\Layanankkprl\Resources\KkprlProposals\RelationManagers\DocumentsRelationManager;
use App\Filament\Layanankkprl\Resources\KkprlProposals\RelationManagers\ReviewEventsRelationManager;
use App\Filament\Layanankkprl\Resources\KkprlProposals\Schemas\KkprlProposalInfolist;
use App\Filament\Layanankkprl\Resources\KkprlProposals\Tables\KkprlProposalsTable;
use App\Models\KkprlProposal;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KkprlProposalResource extends Resource
{
    protected static ?string $model = KkprlProposal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static string|\UnitEnum|null $navigationGroup = 'Proposal KKPRL';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Review Proposal';
    }

    public static function getModelLabel(): string
    {
        return 'Proposal KKPRL';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Proposal KKPRL';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return KkprlProposalsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KkprlProposalInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            AttachmentsRelationManager::class,
            DocumentsRelationManager::class,
            ReviewEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKkprlProposals::route('/'),
            'view' => ViewKkprlProposal::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['attachments', 'documents']);
    }
}
