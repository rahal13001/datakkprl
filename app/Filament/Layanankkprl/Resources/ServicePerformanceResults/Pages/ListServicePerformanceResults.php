<?php

namespace App\Filament\Layanankkprl\Resources\ServicePerformanceResults\Pages;

use App\Filament\Layanankkprl\Resources\ServicePerformanceResults\ServicePerformanceResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServicePerformanceResults extends ListRecords
{
    protected static string $resource = ServicePerformanceResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
