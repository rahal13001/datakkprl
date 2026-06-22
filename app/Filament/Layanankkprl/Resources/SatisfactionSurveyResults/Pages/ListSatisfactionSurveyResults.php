<?php

namespace App\Filament\Layanankkprl\Resources\SatisfactionSurveyResults\Pages;

use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResults\SatisfactionSurveyResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSatisfactionSurveyResults extends ListRecords
{
    protected static string $resource = SatisfactionSurveyResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
