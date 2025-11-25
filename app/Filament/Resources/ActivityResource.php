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
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Manajemen KKPRL';

    protected static ?string $modelLabel = 'Pentek';

    protected static ?string $pluralModelLabel = 'Pentek';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Pentek')
                    ->description('Detail umum tentang kegiatan dan lokasinya.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->label('Subjek')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->maxLength(65535),
                            ])
                            ->required()
                            ->columnSpanFull(),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subjek')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->searchable()
                    ->sortable()
                    ->label('Kota/Kabupaten'),
                Tables\Columns\TextColumn::make('organizer')
                    ->label('Penyelenggara')
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
                    ->label('Satuan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ExportAction::make()
                    ->label('Ekspor Excel')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->except(['index'])
                            ->withFilename(fn ($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withColumns([
                                Column::make('subject.name')->heading('Subjek'),
                                Column::make('date')->heading('Tanggal'),
                                Column::make('activity_code')->heading('Kode Kegiatan'),
                                Column::make('city.province.name')->heading('Provinsi'),
                                Column::make('city.name')->heading('Kota/Kabupaten'),
                                Column::make('organizer')->heading('Penyelenggara'),
                                Column::make('application_size')->heading('Ukuran (Permohonan)'),
                                Column::make('technical_assessment_size')->heading('Ukuran (Teknis)'),
                                Column::make('unit')->heading('Satuan'),
                                Column::make('pnbp_potential')->heading('Potensi PNBP'),
                                Column::make('zero_rupiah_incentive')->heading('Insentif Nol Rupiah'),
                                Column::make('detail')->heading('Detail'),
                                Column::make('remarks')->heading('Keterangan'),
                            ]),
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()->label('Lihat'),
                \Filament\Actions\EditAction::make()->label('Ubah'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('Hapus'),
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()
                        ->label('Ekspor Terpilih')
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->except(['index'])
                                ->withFilename(fn ($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                                ->withColumns([
                                    Column::make('subject.name')->heading('Subjek'),
                                    Column::make('date')->heading('Tanggal'),
                                    Column::make('activity_code')->heading('Kode Kegiatan'),
                                    Column::make('city.province.name')->heading('Provinsi'),
                                    Column::make('city.name')->heading('Kota/Kabupaten'),
                                    Column::make('organizer')->heading('Penyelenggara'),
                                    Column::make('application_size')->heading('Ukuran (Permohonan)'),
                                    Column::make('technical_assessment_size')->heading('Ukuran (Teknis)'),
                                    Column::make('unit')->heading('Satuan'),
                                    Column::make('pnbp_potential')->heading('Potensi PNBP'),
                                    Column::make('zero_rupiah_incentive')->heading('Insentif Nol Rupiah'),
                                    Column::make('detail')->heading('Detail'),
                                    Column::make('remarks')->heading('Keterangan'),
                                ]),
                        ]),
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
                \Filament\Schemas\Components\Section::make('Informasi Kegiatan')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('subject.name')
                            ->label('Subjek'),
                        \Filament\Infolists\Components\TextEntry::make('date')
                            ->label('Tanggal')
                            ->date('d-m-Y'),
                        \Filament\Infolists\Components\TextEntry::make('activity_code')
                            ->label('Kode Kegiatan'),
                        \Filament\Infolists\Components\TextEntry::make('city.province.name')
                            ->label('Provinsi'),
                        \Filament\Infolists\Components\TextEntry::make('city.name')
                            ->label('Kota/Kabupaten'),
                        \Filament\Infolists\Components\TextEntry::make('organizer')
                            ->label('Penyelenggara'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Metrik Teknis & Finansial')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('application_size')
                            ->label('Ukuran (Permohonan)'),
                        \Filament\Infolists\Components\TextEntry::make('technical_assessment_size')
                            ->label('Ukuran (Teknis)'),
                        \Filament\Infolists\Components\TextEntry::make('unit')
                            ->label('Satuan'),
                        \Filament\Infolists\Components\TextEntry::make('pnbp_potential')
                            ->label('Potensi PNBP')
                            ->money('IDR'),
                        \Filament\Infolists\Components\TextEntry::make('zero_rupiah_incentive')
                            ->label('Insentif Nol Rupiah')
                            ->money('IDR'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Detail Tambahan')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('detail')
                            ->label('Detail')
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
