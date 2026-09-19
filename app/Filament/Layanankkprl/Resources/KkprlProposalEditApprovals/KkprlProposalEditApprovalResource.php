<?php

namespace App\Filament\Layanankkprl\Resources\KkprlProposalEditApprovals;

use App\Filament\Layanankkprl\Resources\KkprlProposalEditApprovals\Pages\ListKkprlProposalEditApprovals;
use App\Filament\Layanankkprl\Resources\KkprlProposalEditApprovals\Tables\KkprlProposalEditApprovalsTable;
use App\Models\KkprlProposalEditApproval;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class KkprlProposalEditApprovalResource extends Resource
{
    protected static ?string $model = KkprlProposalEditApproval::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Proposal KKPRL';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Approval Edit Darurat';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return KkprlProposalEditApprovalsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListKkprlProposalEditApprovals::route('/')];
    }
}
