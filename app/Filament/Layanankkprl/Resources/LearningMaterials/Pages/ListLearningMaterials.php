<?php

namespace App\Filament\Layanankkprl\Resources\LearningMaterials\Pages;

use App\Filament\Layanankkprl\Resources\LearningMaterials\LearningMaterialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLearningMaterials extends ListRecords
{
    protected static string $resource = LearningMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
