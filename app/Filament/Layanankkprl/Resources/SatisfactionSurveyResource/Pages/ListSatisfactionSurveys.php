<?php

namespace App\Filament\Layanankkprl\Resources\SatisfactionSurveyResource\Pages;

use App\Filament\Layanankkprl\Resources\SatisfactionSurveyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSatisfactionSurveys extends ListRecords
{
    protected static string $resource = SatisfactionSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
