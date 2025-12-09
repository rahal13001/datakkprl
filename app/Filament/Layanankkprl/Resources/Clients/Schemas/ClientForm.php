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
                        Select::make('status')
                            ->options([
                                'pending' => 'Menunggu',
                                'scheduled' => 'Dijadwalkan',
                                'in_progress' => 'Dalam Proses',
                                'finished' => 'Selesai',
                                'canceled' => 'Dibatalkan',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Detail Kontak')
                    ->description('Informasi kontak pemohon layanan.')
                    ->schema([
                        TextInput::make('contact_details.name')
                            ->label('Nama Lengkap')
                            ->required(),
                        TextInput::make('contact_details.email')
                            ->label('Email')
                            ->email()
                            ->required(),
                        TextInput::make('contact_details.wa')
                            ->label('WhatsApp')
                            ->tel()
                            ->required(),
                        TextInput::make('contact_details.agency')
                            ->label('Instansi / Lembaga')
                            ->required(),
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
                        \Filament\Forms\Components\KeyValue::make('metadata')
                            ->label('Data Tambahan'),
                    ])
                    ->collapsed(),
            ]);
    }
}
