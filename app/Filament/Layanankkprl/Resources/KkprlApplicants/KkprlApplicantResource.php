<?php

namespace App\Filament\Layanankkprl\Resources\KkprlApplicants;

use App\Filament\Layanankkprl\Resources\KkprlApplicants\Pages\ListKkprlApplicants;
use App\Filament\Layanankkprl\Resources\KkprlApplicants\Pages\ViewKkprlApplicant;
use App\Filament\Layanankkprl\Resources\KkprlApplicants\Tables\KkprlApplicantsTable;
use App\Models\KkprlApplicant;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class KkprlApplicantResource extends Resource
{
    protected static ?string $model = KkprlApplicant::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Proposal KKPRL';

    protected static ?string $modelLabel = 'Akun pemohon';

    protected static ?string $pluralModelLabel = 'Akun pemohon';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('email')->label('Email'),
            TextEntry::make('approved_at')->label('Approval builder')->dateTime()->placeholder('Belum disetujui'),
            TextEntry::make('approved_by')->label('ID petugas approval')->placeholder('—'),
            TextEntry::make('created_at')->label('Terdaftar')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return KkprlApplicantsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListKkprlApplicants::route('/'), 'view' => ViewKkprlApplicant::route('/{record}')];
    }
}
