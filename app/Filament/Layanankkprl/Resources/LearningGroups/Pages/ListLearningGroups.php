<?php

namespace App\Filament\Layanankkprl\Resources\LearningGroups\Pages;

use App\Filament\Layanankkprl\Resources\LearningGroups\LearningGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLearningGroups extends ListRecords
{
    protected static string $resource = LearningGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
