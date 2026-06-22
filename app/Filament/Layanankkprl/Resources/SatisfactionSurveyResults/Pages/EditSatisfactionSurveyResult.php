<?php

namespace App\Filament\Layanankkprl\Resources\SatisfactionSurveyResults\Pages;

use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResults\SatisfactionSurveyResultResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSatisfactionSurveyResult extends EditRecord
{
    protected static string $resource = SatisfactionSurveyResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
