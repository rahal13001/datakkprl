<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use App\Models\City;
use App\Models\Province;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

use Filament\Schemas\Schema;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'KKPRL Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Activity Information')
                    ->description('General details about the activity and its location.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(65535),
                            ])
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('date')
                            ->required(),
                        Forms\Components\TextInput::make('activity_code')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Forms\Components\Select::make('province_id')
                            ->label('Province')
                            ->options(Province::query()->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('city_id', null))
                            ->required()
                            ->dehydrated(false)
                            ->formatStateUsing(function (Get $get, ?Activity $record) {
                                if ($record && $record->city) {
                                    return $record->city->province_id;
                                }
                                return null;
                            }),
                        Forms\Components\Select::make('city_id')
                            ->label('City')
                            ->options(fn (Get $get): Collection => City::query()
                                ->where('province_id', $get('province_id'))
                                ->pluck('name', 'id'))
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('organizer')
                            ->options([
                                'Pusat' => 'Pusat',
                                'UPT' => 'UPT',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Technical & Financial Metrics')
                    ->description('Measurements and financial details.')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\TextInput::make('application_size')
                            ->numeric()
                            ->label('Ukuran (Permohonan)'),
                        Forms\Components\TextInput::make('technical_assessment_size')
                            ->numeric()
                            ->label('Ukuran (Teknis)'),
                        Forms\Components\Select::make('unit')
                            ->options([
                                'Hektar' => 'Hektar',
                                'Kilometer' => 'Kilometer',
                            ]),
                        Forms\Components\TextInput::make('pnbp_potential')
                            ->numeric()
                            ->label('Potensi PNBP')
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('zero_rupiah_incentive')
                            ->numeric()
                            ->label('Insentif Nol Rupiah')
                            ->prefix('Rp'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Additional Details')
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Textarea::make('detail')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('remarks')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->searchable()
                    ->sortable()
                    ->label('City'),
                Tables\Columns\TextColumn::make('organizer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('application_size')
                    ->numeric()
                    ->label('Ukuran (Permohonan)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('technical_assessment_size')
                    ->numeric()
                    ->label('Ukuran (Teknis)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Date from ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString())
                                ->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Date until ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString())
                                ->removeField('until');
                        }
                        return $indicators;
                    }),
                SelectFilter::make('organizer')
                    ->options([
                        'Pusat' => 'Pusat',
                        'UPT' => 'UPT',
                    ])
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('province')
                    ->label('Province')
                    ->options(Province::query()->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $query, $value) => $query->whereHas('city', fn (Builder $query) => $query->where('province_id', $value))
                    ))
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('city_id')
                    ->label('City')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Activity Information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('subject.name')
                            ->label('Subject'),
                        \Filament\Infolists\Components\TextEntry::make('date')
                            ->date('d-m-Y'),
                        \Filament\Infolists\Components\TextEntry::make('activity_code'),
                        \Filament\Infolists\Components\TextEntry::make('city.province.name')
                            ->label('Province'),
                        \Filament\Infolists\Components\TextEntry::make('city.name')
                            ->label('City'),
                        \Filament\Infolists\Components\TextEntry::make('organizer'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Technical & Financial Metrics')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('application_size')
                            ->label('Ukuran (Permohonan)'),
                        \Filament\Infolists\Components\TextEntry::make('technical_assessment_size')
                            ->label('Ukuran (Teknis)'),
                        \Filament\Infolists\Components\TextEntry::make('unit'),
                        \Filament\Infolists\Components\TextEntry::make('pnbp_potential')
                            ->label('Potensi PNBP')
                            ->money('IDR'),
                        \Filament\Infolists\Components\TextEntry::make('zero_rupiah_incentive')
                            ->label('Insentif Nol Rupiah')
                            ->money('IDR'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Additional Details')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('detail')
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('remarks')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'view' => Pages\ViewActivity::route('/{record}'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
