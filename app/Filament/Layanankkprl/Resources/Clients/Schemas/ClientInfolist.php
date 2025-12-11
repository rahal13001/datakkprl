<?php

namespace App\Filament\Layanankkprl\Resources\Clients\Schemas;

use App\Models\Client;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Layanan')
                    ->schema([
                        TextEntry::make('ticket_number')
                            ->label('Nomor Tiket')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('service.name')
                            ->label('Layanan'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'gray',
                                'scheduled' => 'warning',
                                'in_progress' => 'info',
                                'waiting_approval' => 'info',
                                'finished' => 'success',
                                'canceled' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Menunggu',
                                'scheduled' => 'Dijadwalkan',
                                'in_progress' => 'Sedang Berlangsung',
                                'waiting_approval' => 'Menunggu Persetujuan',
                                'finished' => 'Selesai',
                                'canceled' => 'Dibatalkan',
                                default => $state,
                            }),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Detail Kontak')
                    ->schema([
                        TextEntry::make('booking_type')
                            ->label('Jenis Pemohon')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'personal' => 'Perorangan',
                                'company' => 'Instansi / Perusahaan',
                                default => $state ?? '-',
                            })
                            ->color('info'),
                        TextEntry::make('name')
                            ->label('Nama Lengkap'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        TextEntry::make('whatsapp')
                            ->label('WhatsApp')
                            ->icon('heroicon-m-phone')
                            ->copyable(),
                        TextEntry::make('instance')
                            ->label('Instansi / Lembaga')
                            ->placeholder('-'),
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Informasi Tambahan')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('activity_type')
                            ->label('Bentuk Kegiatan')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'business' => 'Berusaha',
                                'non_business' => 'Non Berusaha',
                                default => $state ?? '-',
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'business' => 'success',
                                'non_business' => 'gray',
                                default => 'gray',
                            }),
                        \Filament\Infolists\Components\RepeatableEntry::make('metadata.data_teknis')
                            ->label('Data Teknis')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('nature')
                                    ->label('Sifat')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'business' => 'Berusaha',
                                        'non_business' => 'Non Berusaha',
                                        default => $state,
                                    })
                                    ->color(fn ($state) => match($state) {
                                        'business' => 'success',
                                        'non_business' => 'gray',
                                        default => 'gray',
                                    }),
                                \Filament\Infolists\Components\TextEntry::make('activity')
                                    ->label('Jenis Kegiatan'),
                                \Filament\Infolists\Components\TextEntry::make('dimension')
                                    ->label('Luasan / Panjang'),
                            ])
                            ->columns(3),
                    ]),

                \Filament\Schemas\Components\Section::make('Jadwal Konsultasi')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('schedules')
                            ->label('')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('date')
                                    ->label('Tanggal')
                                    ->date('l, d F Y')
                                    ->icon('heroicon-m-calendar'),
                                \Filament\Infolists\Components\TextEntry::make('start_time')
                                    ->label('Mulai')
                                    ->time('H:i'),
                                \Filament\Infolists\Components\TextEntry::make('end_time')
                                    ->label('Selesai')
                                    ->time('H:i'),
                                \Filament\Infolists\Components\TextEntry::make('is_online')
                                    ->label('Tipe')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Online' : 'Offline')
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                \Filament\Infolists\Components\TextEntry::make('meeting_link')
                                    ->label('Link Meeting')
                                    ->icon('heroicon-m-link')
                                    ->copyable()
                                    ->visible(fn ($record) => $record->is_online && filled($record->meeting_link))
                                    ->columnSpanFull(),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
