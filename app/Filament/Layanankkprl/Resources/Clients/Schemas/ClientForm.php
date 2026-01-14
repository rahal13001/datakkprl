<?php

namespace App\Filament\Layanankkprl\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Layanan')
                    ->schema([
                        TextInput::make('ticket_number')
                            ->label('Nomor Tiket')
                            ->placeholder('Otomatis dibuat sistem')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
                        Select::make('service_id')
                            ->label('Layanan')
                            ->relationship('service', 'name')
                            ->required(),
                        Select::make('consultation_location_id')
                            ->label('Lokasi Konsultasi')
                            ->relationship('consultationLocation', 'name')
                            ->preload()
                            ->nullable(),
                        Select::make('status')
                            ->options([
                                'waiting' => 'Menunggu',
                                'scheduled' => 'Dijadwalkan',
                                'completed' => 'Selesai',
                            ])
                            ->default('waiting')
                            ->required(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Detail Kontak')
                    ->description('Informasi kontak pemohon layanan.')
                    ->schema([
                        Select::make('booking_type')
                            ->label('Jenis Pemohon')
                            ->options([
                                'personal' => 'Perorangan',
                                'company' => 'Perusahaan / Instansi',
                            ])
                            ->default('personal')
                            ->live()
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),
                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->required(),
                        TextInput::make('instance')
                            ->label('Nama Instansi / Perusahaan')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('booking_type') === 'company')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('booking_type') === 'company')
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Jadwal Konsultasi')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('schedules')
                            ->relationship()
                            ->label('Sesi Pertemuan')
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal')
                                    ->required(),
                                \Filament\Forms\Components\TimePicker::make('start_time')
                                    ->label('Mulai')
                                    ->required(),
                                \Filament\Forms\Components\TimePicker::make('end_time')
                                    ->label('Selesai')
                                    ->required(),
                                \Filament\Forms\Components\Toggle::make('is_online')
                                    ->label('Online?')
                                    ->columnSpanFull()
                                    ->live(),
                                \Filament\Forms\Components\TextInput::make('meeting_link')
                                    ->label('Link')
                                    ->placeholder('URL')
                                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_online'))
                                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_online'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->addActionLabel('Tambah Sesi'),
                    ]),

                \Filament\Schemas\Components\Section::make('Informasi Tambahan')
                    ->schema([
                        \Filament\Forms\Components\Select::make('activity_type')
                            ->label('Sifat Kegiatan')
                            ->options([
                                'business' => 'Berusaha',
                                'non_business' => 'Non Berusaha',
                            ]),
                        \Filament\Forms\Components\Repeater::make('metadata.data_teknis')
                            ->label('Data Teknis')
                            ->schema([
                                \Filament\Forms\Components\Select::make('nature')
                                    ->label('Sifat')
                                    ->options([
                                        'non_business' => 'Non Berusaha',
                                        'business' => 'Berusaha',
                                    ])
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('activity')
                                    ->label('Jenis Kegiatan')
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('location')
                                    ->label('Lokasi (Kab/Kota)')
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('dimension')
                                    ->label('Luasan / Panjang')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Data')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                \Filament\Schemas\Components\Section::make('Dokumen Pendukung')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('supporting_documents_display')
                            ->label('Dokumen Pendukung')
                            ->content(function ($record) {
                                if (!$record || empty($record->supporting_documents)) {
                                    return 'Tidak ada dokumen.';
                                }
                                $links = collect($record->supporting_documents)->map(function ($path) {
                                    $url = \Storage::disk('public')->url($path);
                                    $name = basename($path);
                                    return "<a href='{$url}' target='_blank' class='text-primary-600 hover:underline'>{$name}</a>";
                                })->join('<br>');
                                return new \Illuminate\Support\HtmlString($links);
                            })
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Placeholder::make('coordinate_file_display')
                            ->label('File Koordinat')
                            ->content(function ($record) {
                                if (!$record || empty($record->coordinate_file)) {
                                    return 'Tidak ada file koordinat.';
                                }
                                $url = \Storage::disk('public')->url($record->coordinate_file);
                                $name = basename($record->coordinate_file);
                                return new \Illuminate\Support\HtmlString("<a href='{$url}' target='_blank' class='text-primary-600 hover:underline'>{$name}</a>");
                            })
                            ->columnSpanFull(),
                    ])
                    ->hiddenOn('create')
                    ->collapsed(),
            ]);
    }
}
