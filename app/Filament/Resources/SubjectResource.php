<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use Filament\Schemas\Schema;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Manajemen KKPRL';

    protected static ?string $modelLabel = 'Subjek';

    protected static ?string $pluralModelLabel = 'Subjek';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()->label('Lihat'),
                \Filament\Actions\EditAction::make()->label('Ubah'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('Hapus'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SubjectResource\RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Detail Subjek')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('name')
                            ->label('Nama'),
                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'view' => Pages\ViewSubject::route('/{record}'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
