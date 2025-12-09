<?php

namespace App\Filament\Layanankkprl\Resources\Holidays\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->required(),
                TextInput::make('description')
                    ->label('Keterangan')
                    ->required(),
            ]);
    }
}
