<?php

namespace App\Filament\Layanankkprl\Resources\SatisfactionSurveyResource\Pages;

use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSatisfactionSurvey extends EditRecord
{
    protected static string $resource = SatisfactionSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
