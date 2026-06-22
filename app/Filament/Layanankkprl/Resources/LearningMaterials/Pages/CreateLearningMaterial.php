<?php

namespace App\Filament\Layanankkprl\Resources\LearningMaterials\Pages;

use App\Filament\Layanankkprl\Resources\LearningMaterials\LearningMaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLearningMaterial extends CreateRecord
{
    protected static string $resource = LearningMaterialResource::class;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }
}
