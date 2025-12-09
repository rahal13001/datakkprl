<?php

namespace App\Filament\Layanankkprl\Resources\Faqs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Konten Tanya Jawab')
                    ->description('Masukkan pertanyaan dan jawaban yang sering diajukan.')
                    ->schema([
                        Textarea::make('question')
                            ->label('Pertanyaan')
                            ->placeholder('Contoh: Bagaimana cara mendaftar?')
                            ->required()
                            ->rows(2)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, $state) => $set('slug', \Illuminate\Support\Str::slug($state)))
                            ->columnSpanFull(),
                        
                        TextInput::make('slug')
                            ->hidden()
                            ->dehydrated()
                            ->required(),
                        
                        Textarea::make('answer')
                            ->label('Jawaban')
                            ->placeholder('Tulis jawaban lengkap di sini...')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                \Filament\Schemas\Components\Section::make('Pengaturan')
                    ->schema([
                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->helperText('Angka lebih kecil tampil lebih dulu.')
                            ->required()
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_published')
                            ->label('Terbitkan Sekarang')
                            ->helperText('Aktifkan agar tampil di website.')
                            ->onIcon('heroicon-m-eye')
                            ->offIcon('heroicon-m-eye-slash')
                            ->inline(false)
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
