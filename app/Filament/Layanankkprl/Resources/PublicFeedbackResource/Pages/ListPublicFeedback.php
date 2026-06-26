<?php

namespace App\Filament\Layanankkprl\Resources\PublicFeedbackResource\Pages;

use App\Filament\Layanankkprl\Resources\PublicFeedbackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPublicFeedback extends ListRecords
{
    protected static string $resource = PublicFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
