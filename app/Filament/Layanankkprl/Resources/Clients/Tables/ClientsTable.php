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
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->columns([
                TextColumn::make('index')->rowIndex()->label('No'),
                
                TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false), // Visible as per user request context implied

                TextColumn::make('ticket_number')
                    ->label('No. Tiket')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama Pemohon')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->instance),

                TextColumn::make('instance')
                    ->label('Instansi')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Hidden by default, but available for sort

                TextColumn::make('service.name')
                    ->label('Layanan')
                    ->searchable()
                    ->badge()
                    ->sortable(),

                TextColumn::make('consultationLocation.name')
                    ->label('Lokasi')
                    ->badge()
                    ->icon(fn ($record) => $record->consultationLocation?->is_online ? 'heroicon-m-video-camera' : 'heroicon-m-building-office')
                    ->color(fn ($record) => $record->consultationLocation?->is_online ? 'success' : 'gray')
                    ->placeholder('-')
                    ->sortable(),

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
                    ->separator(',')
                    // Custom sort for HasMany relationship (using min date)
                    ->sortable(query: function (\Illuminate\Database\Eloquent\Builder $query, string $direction): \Illuminate\Database\Eloquent\Builder {
                        return $query->orderBy(
                            \App\Models\Schedule::select('date')
                                ->whereColumn('schedules.client_id', 'clients.id')
                                ->orderBy('date', 'asc') // Always pick earliest date for comparison
                                ->limit(1),
                            $direction
                        );
                    }),

                IconColumn::make('report_status_display')
                    ->label('Laporan')
                    ->state(fn ($record) => $record->consultationReports()->latest()->first()?->status ?? 'none')
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'completed' => 'success',
                        default => 'danger',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'draft' => 'heroicon-m-document',
                        'completed' => 'heroicon-m-check-circle',
                        default => 'heroicon-m-x-mark',
                    })
                    ->alignCenter()
                    ->tooltip(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'completed' => 'Selesai',
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
                                \Filament\Forms\Components\FileUpload::make('documentation')
                                    ->label('Dokumentasi')
                                    ->disk('public')
                                    ->image()
                                    ->multiple()
                                    ->minFiles(1)
                                    ->maxFiles(3)
                                    ->directory('consultation-documentation')
                                    ->placeholder('Upload minimal 1 foto dokumentasi, maksimal 3 foto.')
                                    ->required(),
                                \Filament\Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'completed' => 'Selesai',
                                    ])
                                    ->required()
                                    ->default('draft')
                                    ->live(),
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
                        'waiting' => 'warning',
                        'scheduled' => 'info',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'waiting' => 'Menunggu',
                        'scheduled' => 'Dijadwalkan',
                        'completed' => 'Selesai',
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
                        'waiting' => 'Menunggu',
                        'scheduled' => 'Dijadwalkan',
                        'completed' => 'Selesai',
                    ])
                    ->label('Status'),

                \Filament\Tables\Filters\SelectFilter::make('consultation_location_id')
                    ->relationship('consultationLocation', 'name')
                    ->label('Lokasi')
                    ->searchable()
                    ->preload(),

                \Filament\Tables\Filters\SelectFilter::make('report_status')
                    ->label('Status Laporan')
                    ->options([
                        'draft' => 'Draft',
                        'completed' => 'Selesai',
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
                Action::make('downloadReport')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->iconButton()
                    ->tooltip('Unduh Laporan PDF')
                    ->action(function (\App\Models\Client $record) {
                        $report = $record->latestConsultationReport;
                        
                        if (!$report) {
                            \Filament\Notifications\Notification::make()
                                ->title('Laporan belum tersedia')
                                ->warning()
                                ->send();
                            return;
                        }

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.consultation-report', [
                            'client' => $record,
                            'report' => $report,
                        ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            "Laporan-Konsultasi-{$record->ticket_number}.pdf"
                        );
                    }),
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()
                    ->exports([
                        \pxlrbt\FilamentExcel\Exports\ExcelExport::make()
                            ->modifyQueryUsing(fn ($query) => $query->with([
                                'schedules.assignments.user',
                                'service', 
                                'latestConsultationReport'
                            ]))
                            ->withFilename(fn ($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withColumns([
                                \pxlrbt\FilamentExcel\Columns\Column::make('ticket_number')->heading('No. Tiket'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('booking_type')
                                    ->heading('Tipe')
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'personal' => 'Perorangan',
                                        'company' => 'Instansi',
                                        default => $state,
                                    }),
                                \pxlrbt\FilamentExcel\Columns\Column::make('name')->heading('Nama Pemohon'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('instance')->heading('Instansi'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('email')->heading('Email'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('whatsapp')->heading('WhatsApp'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('address')->heading('Alamat'),
                                
                                \pxlrbt\FilamentExcel\Columns\Column::make('service.name')->heading('Layanan'),
                                
                                \pxlrbt\FilamentExcel\Columns\Column::make('activity_type')
                                    ->heading('Bentuk Kegiatan')
                                    ->getStateUsing(fn ($record) => match ($record->activity_type) {
                                        'business' => 'Berusaha',
                                        'non_business' => 'Non Berusaha',
                                        default => $record->activity_type,
                                    }),

                                \pxlrbt\FilamentExcel\Columns\Column::make('metadata_string')
                                    ->heading('Data Teknis')
                                    ->getStateUsing(function ($record) {
                                        if (empty($record->metadata)) return '-';
                                        return collect($record->metadata)
                                            ->map(fn ($value, $key) => "$key: $value")
                                            ->join('; ');
                                    }),

                                \pxlrbt\FilamentExcel\Columns\Column::make('schedule_details')
                                    ->heading('Jadwal Konsultasi')
                                    ->getStateUsing(function ($record) {
                                        if (!$record->schedules) return '-';
                                        return $record->schedules->map(function ($schedule) {
                                            $date = \Carbon\Carbon::parse($schedule->date)->format('d M Y');
                                            $time = \Carbon\Carbon::parse($schedule->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($schedule->end_time)->format('H:i');
                                            $type = $schedule->is_online ? 'Online' : 'Offline';
                                            return "{$date} ({$time}, {$type})";
                                        })->join("\n");
                                    }),

                                \pxlrbt\FilamentExcel\Columns\Column::make('status')
                                    ->heading('Status')
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'waiting' => 'Menunggu',
                                        'scheduled' => 'Dijadwalkan',
                                        'completed' => 'Selesai',
                                        default => $state,
                                    }),

                                \pxlrbt\FilamentExcel\Columns\Column::make('report_status')
                                    ->heading('Status Laporan')
                                    ->getStateUsing(fn ($record) => match ($record->latestConsultationReport?->status ?? 'none') {
                                        'draft' => 'Draft',
                                        'completed' => 'Selesai',
                                        'none' => 'Belum Ada',
                                        default => '-',
                                    }),

                                \pxlrbt\FilamentExcel\Columns\Column::make('assignments_names')
                                    ->heading('Petugas')
                                    ->getStateUsing(fn ($record) => $record->assignments()->with('user')->get()->pluck('user.name')->join(', ')),
                            ]),
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()
                        ->exports([
                            \pxlrbt\FilamentExcel\Exports\ExcelExport::make()
                                ->modifyQueryUsing(fn ($query) => $query->with([
                                    'schedules.assignments.user',
                                    'service', 
                                    'latestConsultationReport'
                                ]))
                                ->withFilename(fn ($resource) => $resource::getModelLabel() . '-Selected-' . date('Y-m-d'))
                                ->withColumns([
                                    \pxlrbt\FilamentExcel\Columns\Column::make('ticket_number')->heading('No. Tiket'),
                                    \pxlrbt\FilamentExcel\Columns\Column::make('name')->heading('Nama Pemohon'),
                                    \pxlrbt\FilamentExcel\Columns\Column::make('instance')->heading('Instansi'),
                                    \pxlrbt\FilamentExcel\Columns\Column::make('email')->heading('Email'),
                                    \pxlrbt\FilamentExcel\Columns\Column::make('whatsapp')->heading('WhatsApp'),
                                    \pxlrbt\FilamentExcel\Columns\Column::make('address')->heading('Alamat'),
                                    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('service.name')->heading('Layanan'),
                                    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('activity_type')
                                        ->heading('Bentuk Kegiatan')
                                        ->getStateUsing(fn ($record) => match ($record->activity_type) {
                                            'business' => 'Berusaha',
                                            'non_business' => 'Non Berusaha',
                                            default => $record->activity_type,
                                        }),
    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('metadata_string')
                                        ->heading('Data Teknis')
                                        ->getStateUsing(function ($record) {
                                            $data = $record->metadata['data_teknis'] ?? [];
                                            if (!empty($data) && is_array($data)) {
                                                return collect($data)
                                                    ->map(function ($row) {
                                                        $activity = $row['activity'] ?? '-';
                                                        $location = $row['location'] ?? '-';
                                                        $dimension = $row['dimension'] ?? '-';
                                                        return "{$activity} di {$location} ({$dimension})";
                                                    })
                                                    ->join('; ');
                                            }
                                            
                                            // Fallback for legacy data
                                            if (!empty($record->metadata)) {
                                                return collect($record->metadata)
                                                    ->map(fn ($value, $key) => is_string($value) ? "$key: $value" : null)
                                                    ->filter()
                                                    ->join('; ');
                                            }
                                            
                                            return '-';
                                        }),
    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('schedule_details')
                                        ->heading('Jadwal Konsultasi')
                                        ->getStateUsing(function ($record) {
                                            if (!$record->schedules) return '-';
                                            return $record->schedules->map(function ($schedule) {
                                                $date = \Carbon\Carbon::parse($schedule->date)->format('d M Y');
                                                $time = \Carbon\Carbon::parse($schedule->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($schedule->end_time)->format('H:i');
                                                $type = $schedule->is_online ? 'Online' : 'Offline';
                                                return "{$date} ({$time}, {$type})";
                                            })->join("\n");
                                        }),
    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('status')
                                        ->heading('Status')
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'waiting' => 'Menunggu',
                                            'scheduled' => 'Dijadwalkan',
                                            'completed' => 'Selesai',
                                            default => $state,
                                        }),
    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('report_status')
                                        ->heading('Status Laporan')
                                        ->getStateUsing(fn ($record) => match ($record->latestConsultationReport?->status ?? 'none') {
                                            'draft' => 'Draft',
                                            'completed' => 'Selesai',
                                            'none' => 'Belum Ada',
                                            default => '-',
                                        }),
    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('assignments_names')
                                        ->heading('Petugas')
                                        ->getStateUsing(fn ($record) => $record->assignments()->with('user')->get()->pluck('user.name')->join(', ')),
                                ]),
                        ]),
                ]),
            ]);
    }
}
