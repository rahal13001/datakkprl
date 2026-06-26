<?php

namespace App\Filament\Layanankkprl\Resources;

use App\Filament\Layanankkprl\Resources\PublicFeedbackResource\Pages;
use App\Models\PublicFeedback;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PublicFeedbackResource extends Resource
{
    protected static ?string $model = PublicFeedback::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Masukan Publik';

    protected static ?string $pluralModelLabel = 'Masukan Publik';

    public static function getNavigationLabel(): string
    {
        return 'Masukan Publik';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Toggle::make('is_anonymous')
                    ->label('Anonim')
                    ->inline(false)
                    ->live()
                    ->default(true)
                    ->required(),

                Forms\Components\TextInput::make('submitter_name')
                    ->label('Nama Pengirim')
                    ->maxLength(255)
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => ! (bool) $get('is_anonymous'))
                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => ! (bool) $get('is_anonymous')),

                Forms\Components\Select::make('users')
                    ->label('Petugas Terkait')
                    ->relationship(
                        'users',
                        'name',
                        fn (Builder $query) => $query
                            ->select('users.*')
                            ->where('users.status', true)
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'Pegawai'))
                                    ->orWhereHas('assignments', fn (Builder $assignmentQuery) => $assignmentQuery->whereNull('assignments.deleted_at'));
                            })
                            ->orderBy('users.name')
                    )
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->jabatan
                        ? "{$record->name} - {$record->jabatan}"
                        : $record->name)
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Kosongkan jika masukan ini bersifat umum dan tidak ditujukan ke petugas tertentu.')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('feedback')
                    ->label('Masukan Utama')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('suggestion')
                    ->label('Saran Perbaikan')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_anonymous')
                    ->label('Anonim')
                    ->boolean(),

                Tables\Columns\TextColumn::make('submitter_display')
                    ->label('Pengirim')
                    ->searchable(['submitter_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('users.name')
                    ->label('Petugas Terkait')
                    ->badge()
                    ->separator(', ')
                    ->placeholder('Masukan umum')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('feedback')
                    ->label('Masukan')
                    ->limit(70)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('suggestion')
                    ->label('Saran')
                    ->limit(70)
                    ->wrap()
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_anonymous')
                    ->label('Status Anonim')
                    ->placeholder('Semua')
                    ->trueLabel('Anonim')
                    ->falseLabel('Non-anonim'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPublicFeedback::route('/'),
            'create' => Pages\CreatePublicFeedback::route('/create'),
            'edit' => Pages\EditPublicFeedback::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('users');
    }
}
