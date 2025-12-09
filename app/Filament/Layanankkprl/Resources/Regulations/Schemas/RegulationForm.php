<?php

namespace App\Filament\Layanankkprl\Resources\Regulations\Schemas;

use Asmit\FilamentUpload\Enums\PdfViewFit;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;

class RegulationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Section 1: Main Information
                \Filament\Schemas\Components\Section::make('Informasi Utama')
                    ->description('Data utama regulasi.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Regulasi')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('document_number')
                            ->label('Nomor Dokumen')
                            ->placeholder('Contoh: PERMEN-KP Nomor 10 Tahun 2024')
                            ->columnSpan(['default' => 1, 'sm' => 3]),

                        Toggle::make('is_published')
                            ->label('Publikasikan?')
                            ->default(true)
                            ->required()
                            ->inline(false)
                            ->columnSpan(['default' => 1, 'sm' => 1]),
                    ])
                    ->columns(['default' => 1, 'sm' => 4]),

                // Section 2: Details
                \Filament\Schemas\Components\Section::make('Detail & Dokumen')
                    ->description('Deskripsi dan file regulasi.')
                    ->schema([
                         Textarea::make('description')
                            ->label('Deskripsi Singkat')
                            ->rows(3)
                            ->columnSpanFull(),
                        AdvancedFileUpload::make('file_path')
                                        ->label('Upload PDF')
                                        ->label('Unggah Dokumen (PDF)')
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->maxSize(10240) // 10MB
                                        ->directory('regulations')
                                        ->disk('public')
                                        ->required()
                                        ->helperText('Maksimal 10MB. Sistem akan otomatis memproses teks untuk AI.')
                                        ->pdfPreviewHeight(400) // Customize preview height
                                        ->pdfDisplayPage(1) // Set default page
                                        ->pdfToolbar(true) // Enable toolbar
                                        ->pdfZoomLevel(100) // Set zoom level
                                        ->pdfFitType(PdfViewFit::FIT) // Set fit type
                                        ->columnSpanFull()
                                        ->pdfNavPanes(true), // Enable navigation panes
                    ]),

                // Hidden Internal Fields
                TextInput::make('download_count')->numeric()->default(0)->visible(false),
                Textarea::make('extracted_text')->visible(false),
            ]);
    }
}
