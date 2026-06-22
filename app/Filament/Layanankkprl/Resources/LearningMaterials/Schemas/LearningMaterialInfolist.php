<?php

namespace App\Filament\Layanankkprl\Resources\LearningMaterials\Schemas;

use App\Models\LearningMaterial;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class LearningMaterialInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Materi')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul Materi')
                            ->weight('bold')
                            ->size('lg')
                            ->columnSpanFull(),

                        TextEntry::make('group.title')
                            ->label('Grup')
                            ->badge(),

                        TextEntry::make('group.category.name')
                            ->label('Kategori')
                            ->badge(),

                        TextEntry::make('type')
                            ->label('Jenis')
                            ->formatStateUsing(fn (string $state): string => $state === LearningMaterial::TYPE_PDF ? 'PDF' : 'Video')
                            ->badge(),

                        TextEntry::make('sort_order')
                            ->label('Urutan')
                            ->badge(),

                        IconEntry::make('is_featured')
                            ->label('Unggulan')
                            ->boolean(),

                        IconEntry::make('is_published')
                            ->label('Publikasi')
                            ->boolean(),

                        TextEntry::make('view_count')
                            ->label('Dilihat')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('download_count')
                            ->label('Diunduh')
                            ->badge()
                            ->color('success'),

                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Section::make('Konten')
                    ->schema([
                        TextEntry::make('pdf_path')
                            ->label('PDF')
                            ->formatStateUsing(fn () => 'Buka PDF')
                            ->url(fn ($record) => $record->pdf_path ? Storage::disk('public')->url($record->pdf_path) : null)
                            ->openUrlInNewTab()
                            ->badge()
                            ->color('success')
                            ->visible(fn ($record): bool => $record?->type === LearningMaterial::TYPE_PDF && filled($record->pdf_path)),

                        TextEntry::make('video_url')
                            ->label('URL Video')
                            ->url(fn ($record) => $record->video_url)
                            ->openUrlInNewTab()
                            ->badge()
                            ->color('info')
                            ->visible(fn ($record): bool => $record?->type === LearningMaterial::TYPE_VIDEO && filled($record->video_url)),

                        ImageEntry::make('thumbnail_path')
                            ->label('Thumbnail')
                            ->disk('public')
                            ->height(180),
                    ]),
            ]);
    }
}
