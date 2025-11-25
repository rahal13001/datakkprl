<?php

namespace App\Filament\Resources\SubjectResource\RelationManagers;

use App\Models\Activity;
use App\Models\City;
use App\Models\Province;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Activity Information')
                    ->description('General details about the activity and its location.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
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
                        Forms\Components\TextInput::make('organizer')
                            ->maxLength(255)
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('activity_code')
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('City')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('application_size')
                    ->label('Ukuran (Permohonan)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('technical_assessment_size')
                    ->label('Ukuran (Teknis)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    public function isReadOnly(): bool
    {
        return false;
    }
}
