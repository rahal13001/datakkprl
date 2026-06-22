<?php

namespace App\Filament\Layanankkprl\Resources\LearningGroups\Pages;

use App\Filament\Layanankkprl\Resources\LearningGroups\LearningGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditLearningGroup extends EditRecord
{
    protected static string $resource = LearningGroupResource::class;

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
