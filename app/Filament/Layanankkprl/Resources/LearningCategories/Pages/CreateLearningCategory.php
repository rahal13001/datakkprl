<?php

namespace App\Filament\Layanankkprl\Resources\LearningCategories\Pages;

use App\Filament\Layanankkprl\Resources\LearningCategories\LearningCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLearningCategory extends CreateRecord
{
    protected static string $resource = LearningCategoryResource::class;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }
}
