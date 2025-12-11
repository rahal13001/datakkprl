<?php

namespace App\Filament\Layanankkprl\Resources\Clients\Pages;

use App\Filament\Layanankkprl\Resources\Clients\ClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function afterCreate(): void
    {
        $client = $this->record;
        
        // Trigger Email Notification
        app(\App\Services\NotificationService::class)->sendBookingCreated($client);
    }
}
