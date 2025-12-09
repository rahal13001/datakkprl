<?php

namespace App\Filament\Layanankkprl\Resources\Regulations\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RegulationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul Regulasi')
                            ->weight('bold')
                            ->size('lg')
                            ->columnSpanFull(),
                        
                        TextEntry::make('document_number')
                            ->label('Nomor Dokumen')
                            ->icon('heroicon-m-document-text'),

                        IconEntry::make('is_published')
                            ->label('Publikasi')
                            ->boolean(),

                        TextEntry::make('download_count')
                            ->label('Total Dilihat')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-m-eye'),
                    ])->columns(3),

                Section::make('Konten Dokumen')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->markdown()
                            ->columnSpanFull(),

                        TextEntry::make('file_path')
                            ->label('Dokumen PDF')
                            ->formatStateUsing(fn () => 'Buka Dokumen')
                            ->url(fn ($record) => \Illuminate\Support\Facades\Storage::disk('public')->url($record->file_path))
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->openUrlInNewTab()
                            ->badge()
                            ->color('success'),
                    ]),
            ]);
    }
}
