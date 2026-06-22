<?php

namespace App\Filament\Layanankkprl\Resources\LearningCategories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LearningCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kategori')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Kategori')
                            ->weight('bold')
                            ->size('lg')
                            ->columnSpanFull(),

                        TextEntry::make('slug')
                            ->label('Slug')
                            ->badge(),

                        TextEntry::make('sort_order')
                            ->label('Urutan')
                            ->badge(),

                        IconEntry::make('is_active')
                            ->label('Aktif')
                            ->boolean(),

                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
