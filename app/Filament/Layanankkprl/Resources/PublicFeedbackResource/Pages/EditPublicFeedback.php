<?php

namespace App\Filament\Layanankkprl\Resources\PublicFeedbackResource\Pages;

use App\Filament\Layanankkprl\Resources\PublicFeedbackResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPublicFeedback extends EditRecord
{
    protected static string $resource = PublicFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
