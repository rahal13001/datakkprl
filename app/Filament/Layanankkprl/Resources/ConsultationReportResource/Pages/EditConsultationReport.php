<?php

namespace App\Filament\Layanankkprl\Resources\ConsultationReportResource\Pages;

use App\Filament\Layanankkprl\Resources\ConsultationReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsultationReport extends EditRecord
{
    protected static string $resource = ConsultationReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
