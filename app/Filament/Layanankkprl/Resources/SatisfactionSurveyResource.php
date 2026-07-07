<?php

namespace App\Filament\Layanankkprl\Resources;

use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResource\Pages;
use App\Models\Client;
use App\Models\SatisfactionSurvey;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
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
                    ->label('Pemohon')
                    ->options(fn (?SatisfactionSurvey $record): array => static::getAvailableClientsQuery($record)
                        ->get(['id', 'name', 'ticket_number'])
                        ->mapWithKeys(fn (Client $client): array => [
                            $client->getKey() => static::getClientOptionLabel($client),
                        ])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => static::getClientOptionLabel(Client::find($value)))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('edit')
                    ->helperText('Hanya klien yang belum memiliki survei kepuasan yang dapat dipilih.')
                    ->rules([
                        fn (?SatisfactionSurvey $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            if (blank($value)) {
                                return;
                            }

                            $alreadyExists = SatisfactionSurvey::query()
                                ->where('client_id', $value)
                                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($alreadyExists) {
                                $fail('Klien ini sudah memiliki survei kepuasan.');
                            }
                        },
                    ])
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('criticism')
                    ->label('Kritik')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('suggestion')
                    ->label('Saran')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('estimated_cost_savings')
                    ->label('Perkiraan Penghematan Biaya')
                    ->prefix('Rp')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(999999999999)
                    ->step(1000)
                    ->required()
                    ->helperText('Perkiraan total biaya yang tidak perlu dikeluarkan klien setelah menerima layanan, misalnya transportasi, penginapan, makan, pencetakan, atau pengiriman berkas. Isi 0 jika tidak ada penghematan.')
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

                Tables\Columns\TextColumn::make('client.ticket_number')
                    ->label('Pemohon')
                    ->formatStateUsing(fn ($state, SatisfactionSurvey $record): string => static::getClientOptionLabel($record->client))
                    ->description(fn (SatisfactionSurvey $record): ?string => $record->client?->instance)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('client', function (Builder $clientQuery) use ($search): void {
                            $clientQuery
                                ->where('ticket_number', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('instance', 'like', "%{$search}%");
                        });
                    }),

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

                Tables\Columns\TextColumn::make('estimated_cost_savings')
                    ->label('Estimasi Penghematan')
                    ->formatStateUsing(fn ($state): string => $state === null
                        ? '-'
                        : 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
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
            'index' => Pages\ListSatisfactionSurveys::route('/'),
            'create' => Pages\CreateSatisfactionSurvey::route('/create'),
            'edit' => Pages\EditSatisfactionSurvey::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('client');
    }

    protected static function getAvailableClientsQuery(?SatisfactionSurvey $record = null): Builder
    {
        return Client::query()
            ->whereDoesntHave('satisfactionSurvey', fn (Builder $query) => $query
                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey())))
            ->orderBy('ticket_number');
    }

    protected static function getClientOptionLabel(?Client $client): string
    {
        if (! $client) {
            return '-';
        }

        return $client->name
            ? "{$client->name} ({$client->ticket_number})"
            : "Pemohon tidak diketahui ({$client->ticket_number})";
    }
}
