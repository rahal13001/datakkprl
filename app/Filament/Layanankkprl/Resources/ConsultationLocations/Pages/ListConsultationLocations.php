<?php

namespace App\Filament\Layanankkprl\Resources\ConsultationLocations\Pages;

use App\Filament\Layanankkprl\Resources\ConsultationLocations\ConsultationLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsultationLocations extends ListRecords
{
    protected static string $resource = ConsultationLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalHeading('Tambah Lokasi Konsultasi')
                ->createAnother(false),
        ];
    }
}
