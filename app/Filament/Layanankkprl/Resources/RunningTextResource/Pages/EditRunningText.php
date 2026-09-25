<?php

namespace App\Filament\Layanankkprl\Resources\RunningTextResource\Pages;

use App\Filament\Layanankkprl\Resources\RunningTextResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRunningText extends EditRecord
{
    protected static string $resource = RunningTextResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
