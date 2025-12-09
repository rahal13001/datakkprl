<?php

namespace App\Filament\Layanankkprl\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('No')->rowIndex()->label('No'),
                TextColumn::make('ticket_number')
                    ->label('No. Tiket')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('contact_details.name')
                    ->label('Nama Pemohon')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service.name')
                    ->label('Layanan')
                    ->searchable()
                    ->badge(),

                TextColumn::make('activity_type')
                    ->label('Sifat Kegiatan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'business' => 'Berusaha',
                        'non_business' => 'Non Berusaha',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'business' => 'success',
                        'non_business' => 'gray',
                        default => 'gray',
                    }),
                
                TextColumn::make('schedule_dates')
                    ->label('Tgl. Konsultasi')
                    ->getStateUsing(function ($record) {
                        $dates = $record->schedules->pluck('date')->sort();

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
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'scheduled' => 'warning',
                        'waiting_approval' => 'info',
                        'finished' => 'success',
                        'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'scheduled' => 'Dijadwalkan',
                        'waiting_approval' => 'Menunggu Persetujuan',
                        'finished' => 'Selesai',
                        'canceled' => 'Dibatalkan',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('service_id')
                    ->relationship('service', 'name')
                    ->label('Layanan')
                    ->searchable()
                    ->preload(),
                
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'scheduled' => 'Dijadwalkan',
                        'waiting_approval' => 'Menunggu Persetujuan',
                        'finished' => 'Selesai',
                        'canceled' => 'Dibatalkan',
                    ])
                    ->label('Status'),

                TrashedFilter::make(),
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
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereHas('schedules', fn ($q) => $q->whereDate('date', '>=', $date)),
                            )
                            ->when(
                                $data['until'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereHas('schedules', fn ($q) => $q->whereDate('date', '<=', $date)),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
