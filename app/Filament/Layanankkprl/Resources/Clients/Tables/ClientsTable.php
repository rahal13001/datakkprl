<?php

namespace App\Filament\Layanankkprl\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Get;

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
                    ->label('Bentuk Kegiatan')
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

                IconColumn::make('report_status_display')
                    ->label('Laporan')
                    ->state(fn ($record) => $record->consultationReports()->latest()->first()?->status ?? 'none')
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'review' => 'warning',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        default => 'danger',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'draft' => 'heroicon-m-document',
                        'review' => 'heroicon-m-clock',
                        'approved' => 'heroicon-m-check-circle',
                        'rejected' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-x-mark',
                    })
                    ->alignCenter()
                    ->tooltip(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'review' => 'Menunggu Review',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'none' => 'Belum Ada',
                        default => 'Status Tidak Diketahui',
                    })
                    ->action(
                        Action::make('manageReport')
                            ->modalHeading('Kelola Laporan Konsultasi')
                            ->modalWidth('2xl')
                            ->form([
                                \Filament\Forms\Components\RichEditor::make('content')
                                    ->label('Isi Laporan')
                                    ->required(),
                                \Filament\Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'review' => 'Menunggu Review',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                    ])
                                    ->required()
                                    ->default('draft')
                                    ->live(),
                                \Filament\Forms\Components\Textarea::make('feedback')
                                    ->label('Feedback')
                                    ->visible(fn ($get) => in_array($get('status'), ['approved', 'rejected'])),
                            ])
                            ->fillForm(fn ($record) => $record->consultationReports()->latest()->first()?->toArray() ?? [])
                            ->action(function (array $data, \App\Models\Client $record) {
                                $report = $record->consultationReports()->latest()->first();
                                if ($report) {
                                    $report->update($data);
                                } else {
                                    $record->consultationReports()->create($data);
                                }
                                \Filament\Notifications\Notification::make()->title('Laporan disimpan')->success()->send();
                            })
                    ),

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

                \Filament\Tables\Filters\SelectFilter::make('report_status')
                    ->label('Status Laporan')
                    ->options([
                        'draft' => 'Draft',
                        'review' => 'Menunggu Review',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query->when(
                            $data['value'],
                            fn (\Illuminate\Database\Eloquent\Builder $q) => $q->whereHas('latestConsultationReport', fn ($sq) => $sq->where('status', $data['value']))
                        );
                    }),

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
