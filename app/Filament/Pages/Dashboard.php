<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationLabel = 'Beranda';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filter Data')
                    ->description('Filter grafik berdasarkan rentang tanggal.')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Dari Tanggal')
                            ->native(false)
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->label('Sampai Tanggal')
                            ->native(false)
                            ->minDate(fn (Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }
}
