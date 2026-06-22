<?php

namespace App\Filament\Layanankkprl\Resources\LearningMaterials\Schemas;

use App\Models\LearningMaterial;
use Asmit\FilamentUpload\Enums\PdfViewFit;
use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LearningMaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Materi')
                    ->description('Materi referensi berupa PDF atau video eksternal.')
                    ->schema([
                        Select::make('learning_group_id')
                            ->label('Grup Pembelajaran')
                            ->relationship('group', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(['default' => 1, 'md' => 2]),

                        TextInput::make('title')
                            ->label('Judul Materi')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 3]),

                        Select::make('type')
                            ->label('Jenis Materi')
                            ->options([
                                LearningMaterial::TYPE_PDF => 'PDF',
                                LearningMaterial::TYPE_VIDEO => 'Video',
                            ])
                            ->default(LearningMaterial::TYPE_PDF)
                            ->native(false)
                            ->live()
                            ->required()
                            ->columnSpan(['default' => 1, 'md' => 1]),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
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

                Section::make('Konten Materi')
                    ->description('Unggah PDF untuk materi PDF, atau isi URL untuk materi video eksternal.')
                    ->schema([
                        AdvancedFileUpload::make('pdf_path')
                            ->label('Unggah PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->directory('learning-materials/pdfs')
                            ->disk('public')
                            ->required(fn (Get $get): bool => $get('type') === LearningMaterial::TYPE_PDF)
                            ->visible(fn (Get $get): bool => $get('type') === LearningMaterial::TYPE_PDF)
                            ->helperText('Wajib untuk materi PDF. Maksimal 10MB.')
                            ->pdfPreviewHeight(260)
                            ->pdfDisplayPage(1)
                            ->pdfToolbar(true)
                            ->pdfZoomLevel(100)
                            ->pdfFitType(PdfViewFit::FIT)
                            ->pdfNavPanes(true)
                            ->columnSpan(['default' => 1, 'xl' => 1]),

                        TextInput::make('video_url')
                            ->label('URL Video')
                            ->url()
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('type') === LearningMaterial::TYPE_VIDEO)
                            ->visible(fn (Get $get): bool => $get('type') === LearningMaterial::TYPE_VIDEO)
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->helperText('Wajib untuk materi video. Gunakan URL video eksternal, misalnya YouTube.')
                            ->columnSpanFull(),

                        FileUpload::make('thumbnail_path')
                            ->label('Thumbnail')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->directory('learning-materials/thumbnails')
                            ->disk('public')
                            ->visibility('public')
                            ->imagePreviewHeight('160')
                            ->helperText('Opsional. Format JPG, PNG, atau WebP. Maksimal 5MB.')
                            ->columnSpan(['default' => 1, 'xl' => 1]),
                    ])
                    ->columns(['default' => 1, 'xl' => 2])
                    ->columnSpanFull(),

                TextInput::make('view_count')
                    ->numeric()
                    ->default(0)
                    ->visible(false),

                TextInput::make('download_count')
                    ->numeric()
                    ->default(0)
                    ->visible(false),
            ]);
    }
}
