<?php

namespace App\Filament\Layanankkprl\Resources;

use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResource\Pages;
use App\Models\SatisfactionSurvey;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SatisfactionSurveyResource extends Resource
{
    protected static ?string $model = SatisfactionSurvey::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static ?string $navigationLabel = 'Kepuasan Masyarakat';
    
    protected static ?string $modelLabel = 'Survei Kepuasan';

    protected static ?string $pluralModelLabel = 'Survei Kepuasan';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name')
                    ->label('Pemohon')
                    ->disabled(),
                    
                Forms\Components\Textarea::make('criticism')
                    ->label('Kritik')
                    ->rows(3)
                    ->columnSpanFull(),
                    
                Forms\Components\Textarea::make('suggestion')
                    ->label('Saran')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Pemohon')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('criticism')
                    ->label('Kritik')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('suggestion')
                    ->label('Saran')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSatisfactionSurveys::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
}
