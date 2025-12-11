<?php

namespace App\Filament\Layanankkprl\Resources\Clients\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConsultationReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'consultationReports'; // Model function name

    protected static ?string $title = 'Laporan Konsultasi';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\RichEditor::make('content')
                    ->label('Isi Laporan')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('documentation')
                    ->label('Dokumentasi')
                    ->disk('public')
                    ->image()
                    ->multiple()
                    ->minFiles(1)
                    ->maxFiles(3)
                    ->directory('consultation-documentation')
                    ->placeholder('Upload minimal 1 foto dokumentasi, maksimal 3 foto.')
                    ->required()
                    ->columnSpanFull(),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'review' => 'Menunggu Review',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('draft')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('schedule_dates')
                    ->label('Tgl. Konsultasi')
                    ->getStateUsing(function ($record) {
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

                \Filament\Tables\Columns\TextColumn::make('status')
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

                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
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
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereHas('client.schedules', fn ($q) => $q->whereDate('date', '>=', $date)),
                            )
                            ->when(
                                $data['until'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereHas('client.schedules', fn ($q) => $q->whereDate('date', '<=', $date)),
                            );
                    }),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
