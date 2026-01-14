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
                        TextEntry::make('consultationLocation.name')
                            ->label('Lokasi Konsultasi')
                            ->icon(fn ($record) => $record->consultationLocation?->is_online ? 'heroicon-m-video-camera' : 'heroicon-m-building-office-2')
                            ->badge()
                            ->color(fn ($record) => $record->consultationLocation?->is_online ? 'success' : 'gray')
                            ->placeholder('Belum dipilih'),
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
                                \Filament\Infolists\Components\TextEntry::make('location')
                                    ->label('Lokasi (Kab/Kota)'),
                                \Filament\Infolists\Components\TextEntry::make('dimension')
                                    ->label('Luasan / Panjang'),
                            ])
                            ->columns(4),
                    ]),

                \Filament\Schemas\Components\Section::make('Dokumen Pendukung')
                    ->icon('heroicon-m-paper-clip')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('supporting_documents')
                            ->label('Dokumen Pendukung')
                            ->state(function ($record) {
                                if (empty($record->supporting_documents)) {
                                    return '<span class="text-gray-400 italic">Tidak ada dokumen pendukung</span>';
                                }
                                $html = '<div class="flex flex-wrap gap-2">';
                                foreach ($record->supporting_documents as $index => $file) {
                                    $num = $index + 1;
                                    $url = asset('storage/' . $file);
                                    $ext = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
                                    $html .= "<a href=\"{$url}\" target=\"_blank\" class=\"inline-flex items-center gap-2 px-3 py-2 bg-primary-50 text-primary-700 rounded-lg hover:bg-primary-100 transition-colors border border-primary-200\">
                                        <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z\"></path></svg>
                                        <span class=\"font-medium\">Dokumen {$num}</span>
                                        <span class=\"text-xs bg-primary-200 px-1.5 py-0.5 rounded\">{$ext}</span>
                                    </a>";
                                }
                                $html .= '</div>';
                                return $html;
                            })
                            ->html(),
                        \Filament\Infolists\Components\TextEntry::make('coordinate_file')
                            ->label('File Koordinat')
                            ->state(function ($record) {
                                if (empty($record->coordinate_file)) {
                                    return '<span class="text-gray-400 italic">Tidak ada file koordinat</span>';
                                }
                                $url = asset('storage/' . $record->coordinate_file);
                                $ext = strtoupper(pathinfo($record->coordinate_file, PATHINFO_EXTENSION));
                                return "<a href=\"{$url}\" target=\"_blank\" class=\"inline-flex items-center gap-2 px-3 py-2 bg-success-50 text-success-700 rounded-lg hover:bg-success-100 transition-colors border border-success-200\">
                                    <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7\"></path></svg>
                                    <span class=\"font-medium\">File Koordinat</span>
                                    <span class=\"text-xs bg-success-200 px-1.5 py-0.5 rounded\">{$ext}</span>
                                </a>";
                            })
                            ->html(),
                    ])
                    ->columns(1)
                    ->visible(fn ($record) => $record->service?->requires_documents || !empty($record->supporting_documents) || !empty($record->coordinate_file)),

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
                                    ->label('Tautan Rapat')
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
