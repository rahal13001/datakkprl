<?php

namespace App\Filament\Layanankkprl\Resources\ServicePerformanceResults\Pages;

use App\Filament\Layanankkprl\Resources\ServicePerformanceResults\ServicePerformanceResultResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServicePerformanceResult extends EditRecord
{
    protected static string $resource = ServicePerformanceResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
