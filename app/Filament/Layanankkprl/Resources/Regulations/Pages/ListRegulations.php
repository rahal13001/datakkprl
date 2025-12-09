<?php

namespace App\Filament\Layanankkprl\Resources\Regulations\Pages;

use App\Filament\Layanankkprl\Resources\Regulations\RegulationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRegulations extends ListRecords
{
    protected static string $resource = RegulationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
