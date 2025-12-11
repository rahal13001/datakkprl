<?php

namespace App\Filament\Layanankkprl\Resources;

use App\Filament\Layanankkprl\Resources\ConsultationReportResource\Pages;
use App\Models\ConsultationReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use UnitEnum;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ConsultationReportResource extends Resource
{
    protected static ?string $model = ConsultationReport::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Konsultasi';
    
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Laporan Konsultasi';
    }

    public static function getModelLabel(): string
    {
        return 'Laporan';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Isi Laporan')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->ticket_number} - " . ($record->contact_details['agency'] ?? 'Instansi Tidak Diketahui'))
                            ->searchable(['ticket_number', 'contact_details->agency'])
                            ->preload()
                            ->required()
                            ->label('Tiket Klien')
                            ->disabledOn('edit'),
                        
                        Forms\Components\RichEditor::make('content')
                            ->label('Hasil Konsultasi / Notulensi')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('documentation')
                            ->label('Dokumentasi')
                            ->image()
                            ->multiple()
                            ->minFiles(1)
                            ->maxFiles(3)
                            ->directory('consultation-documentation')
                            ->placeholder('Upload minimal 1 foto dokumentasi, maksimal 3 foto.')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(['lg' => 2]),

                \Filament\Schemas\Components\Section::make('Status & Persetujuan')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'review' => 'Menunggu Review',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ])
                            ->required()
                            ->default('draft')
                            ->live(),

                        Forms\Components\Textarea::make('feedback')
                            ->label('Catatan Reviewer')
                            ->visible(fn ($get) => in_array($get('status'), ['rejected', 'approved']))
                            ->placeholder('Berikan alasan penolakan atau catatan tambahan...'),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.ticket_number')
                    ->label('Tiket')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('client.contact_details.name')
                    ->label('Nama Pemohon')
                    ->toggleable(),

                TextColumn::make('client.contact_details.agency')
                    ->label('Instansi')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('schedule_dates')
                    ->label('Tgl. Konsultasi')
                    ->getStateUsing(function ($record) {
                        // Schedules relationship must be loaded or accessed
                        // Assuming Client has 'schedules' HasMany
                        $dates = $record->client?->schedules->pluck('date')->sort();

                        if (!$dates || $dates->isEmpty()) {
                            return '-';
                        }

                        $min = \Carbon\Carbon::parse($dates->first())->format('d M Y');
                        
                        if ($dates->count() === 1) {
                            return $min;
                        }

                        $max = \Carbon\Carbon::parse($dates->last())->format('d M Y');
                        return "{$min} - {$max}";
                    })
                    ->badge()
                    ->color('info')
                    ->separator(','),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'review' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'review' => 'Butuh Review',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'review' => 'Menunggu Review',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
                
                \Filament\Tables\Filters\Filter::make('consultation_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereHas('client.schedules', fn ($q) => $q->whereDate('date', '>=', $date)),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereHas('client.schedules', fn ($q) => $q->whereDate('date', '<=', $date)),
                            );
                    }),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultationReports::route('/'),
            'create' => Pages\CreateConsultationReport::route('/create'),
            'edit' => Pages\EditConsultationReport::route('/{record}/edit'),
        ];
    }
}
