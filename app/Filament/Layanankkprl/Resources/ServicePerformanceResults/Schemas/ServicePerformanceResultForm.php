<?php

namespace App\Filament\Layanankkprl\Resources\ServicePerformanceResults\Schemas;

use App\Models\ServicePerformanceResult;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServicePerformanceResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Periode Publikasi')
                    ->description('Pilih tahun dan triwulan informasi kinerja layanan.')
                    ->schema([
                        TextInput::make('year')
                            ->label('Tahun')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(now()->year + 1)
                            ->default(now()->year),

                        Select::make('quarter')
                            ->label('Triwulan')
                            ->required()
                            ->native(false)
                            ->options([
                                1 => 'TW I',
                                2 => 'TW II',
                                3 => 'TW III',
                                4 => 'TW IV',
                            ])
                            ->rules([
                                fn ($record, $get): Closure => function (string $attribute, $value, Closure $fail) use ($record, $get): void {
                                    $year = (int) $get('year');

                                    if (! $year || ! $value) {
                                        return;
                                    }

                                    $exists = ServicePerformanceResult::query()
                                        ->where('year', $year)
                                        ->where('quarter', (int) $value)
                                        ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                        ->exists();

                                    if ($exists) {
                                        $fail('Informasi kinerja untuk tahun dan triwulan ini sudah ada.');
                                    }
                                },
                            ]),

                        Toggle::make('is_published')
                            ->label('Publikasikan')
                            ->default(true)
                            ->inline(false)
                            ->required(),

                        DateTimePicker::make('published_at')
                            ->label('Tanggal Publikasi')
                            ->seconds(false)
                            ->native(false),
                    ])
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                    ->columnSpanFull(),

                Section::make('Konten Informasi Kinerja')
                    ->description('Unggah gambar informasi kinerja jika tersedia. Tabel kinerja layanan akan dihitung otomatis dari data layanan.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->placeholder('Contoh: Hasil Kinerja Layanan Triwulan I 2026')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),

                        FileUpload::make('image_path')
                            ->label('Gambar Informasi Kinerja')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->directory('service-performance-results')
                            ->disk('public')
                            ->visibility('public')
                            ->imagePreviewHeight('260')
                            ->helperText('Opsional. Format JPG, PNG, atau WebP. Maksimal 5MB.')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
