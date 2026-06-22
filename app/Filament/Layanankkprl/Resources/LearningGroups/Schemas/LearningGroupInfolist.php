<?php

namespace App\Filament\Layanankkprl\Resources\LearningGroups\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LearningGroupInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Grup')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul Grup')
                            ->weight('bold')
                            ->size('lg')
                            ->columnSpanFull(),

                        TextEntry::make('category.name')
                            ->label('Kategori')
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

                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Section::make('Media')
                    ->schema([
                        ImageEntry::make('thumbnail_path')
                            ->label('Thumbnail')
                            ->disk('public')
                            ->height(180),
                    ]),
            ]);
    }
}
