<?php

namespace App\Filament\Layanankkprl\Resources\LearningGroups\Pages;

use App\Filament\Layanankkprl\Resources\LearningGroups\LearningGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLearningGroup extends CreateRecord
{
    protected static string $resource = LearningGroupResource::class;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }
}
