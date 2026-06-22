<?php

namespace App\Filament\Layanankkprl\Resources\LearningGroups\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LearningGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Grup')
                    ->description('Tema pembelajaran di dalam kategori Belajar KKPRL.')
                    ->schema([
                        Select::make('learning_category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(['default' => 1, 'md' => 2]),

                        TextInput::make('title')
                            ->label('Judul Grup')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 4]),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),

                        FileUpload::make('thumbnail_path')
                            ->label('Thumbnail')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->directory('learning-groups')
                            ->disk('public')
                            ->visibility('public')
                            ->imagePreviewHeight('180')
                            ->helperText('Opsional. Format JPG, PNG, atau WebP. Maksimal 5MB.')
                            ->columnSpanFull(),

                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(['default' => 1, 'md' => 2]),

                        Toggle::make('is_featured')
                            ->label('Unggulan')
                            ->default(false)
                            ->inline(false)
                            ->columnSpan(['default' => 1, 'md' => 2]),

                        Toggle::make('is_published')
                            ->label('Publikasikan')
                            ->default(false)
                            ->inline(false)
                            ->required()
                            ->columnSpan(['default' => 1, 'md' => 2]),
                    ])
                    ->columns(['default' => 1, 'md' => 6])
                    ->columnSpanFull(),
            ]);
    }
}
