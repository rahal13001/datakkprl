<?php

namespace App\Filament\Layanankkprl\Resources\LearningCategories\Pages;

use App\Filament\Layanankkprl\Resources\LearningCategories\LearningCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditLearningCategory extends EditRecord
{
    protected static string $resource = LearningCategoryResource::class;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
