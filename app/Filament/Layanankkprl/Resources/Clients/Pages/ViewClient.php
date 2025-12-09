<?php

namespace App\Filament\Layanankkprl\Resources\Clients\Pages;

use App\Filament\Layanankkprl\Resources\Clients\ClientResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            \Filament\Actions\Action::make('download_ticket')
                ->label('Unduh Tiket')
                ->icon('heroicon-m-arrow-down-tray')
                ->url(fn () => route('client.ticket.download', $this->getRecord()))
                ->openUrlInNewTab(),
        ];
    }
}
