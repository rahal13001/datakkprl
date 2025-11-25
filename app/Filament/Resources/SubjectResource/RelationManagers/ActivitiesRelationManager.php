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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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
                \Filament\Schemas\Components\Section::make('Informasi Kegiatan')
                    ->description('Detail umum tentang kegiatan dan lokasinya.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal')
                            ->required(),
                        Forms\Components\TextInput::make('activity_code')
                            ->label('Kode Kegiatan')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Forms\Components\Select::make('province_id')
                            ->label('Provinsi')
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
                            ->label('Kota/Kabupaten')
                            ->options(fn (Get $get): Collection => City::query()
                                ->where('province_id', $get('province_id'))
                                ->pluck('name', 'id'))
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('organizer')
                            ->label('Penyelenggara')
                            ->options([
                                'Pusat' => 'Pusat',
                                'UPT' => 'UPT',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Metrik Teknis & Finansial')
                    ->description('Pengukuran dan detail finansial.')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\TextInput::make('application_size')
                            ->numeric()
                            ->label('Ukuran (Permohonan)'),
                        Forms\Components\TextInput::make('technical_assessment_size')
                            ->numeric()
                            ->label('Ukuran (Teknis)'),
                        Forms\Components\Select::make('unit')
                            ->label('Satuan')
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

                \Filament\Schemas\Components\Section::make('Detail Tambahan')
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Textarea::make('detail')
                            ->label('Detail')
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
                    ->label('Tanggal')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('Kota/Kabupaten')
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
                    ->label('Satuan')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('date')
                    ->label('Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
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
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Dari Tanggal ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString())
                                ->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Sampai Tanggal ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString())
                                ->removeField('until');
                        }
                        return $indicators;
                    }),
                SelectFilter::make('organizer')
                    ->label('Penyelenggara')
                    ->options([
                        'Pusat' => 'Pusat',
                        'UPT' => 'UPT',
                    ])
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('province')
                    ->label('Provinsi')
                    ->options(Province::query()->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $query, $value) => $query->whereHas('city', fn (Builder $query) => $query->where('province_id', $value))
                    ))
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('city_id')
                    ->label('Kota/Kabupaten')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->groups([
                Tables\Grouping\Group::make('activity_code')
                    ->label('Pentek'),
                Tables\Grouping\Group::make('organizer')
                    ->label('Penyelenggara'),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()->label('Buat Baru'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()->label('Lihat'),
                    \Filament\Actions\EditAction::make()->label('Ubah'),
                    \Filament\Actions\DeleteAction::make()->label('Hapus'),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('Hapus'),
                ]),
            ]);
    }
    public function isReadOnly(): bool
    {
        return false;
    }
}
