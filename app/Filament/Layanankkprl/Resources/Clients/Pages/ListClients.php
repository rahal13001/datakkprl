<?php

namespace App\Filament\Layanankkprl\Resources\Clients\Pages;

use App\Filament\Layanankkprl\Resources\Clients\ClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua Klien'),
            'pending' => \Filament\Schemas\Components\Tabs\Tab::make('Menunggu')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'pending'))
                ->badge(\App\Models\Client::where('status', 'pending')->count()),
            'scheduled' => \Filament\Schemas\Components\Tabs\Tab::make('Dijadwalkan')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'scheduled'))
                ->badge(\App\Models\Client::where('status', 'scheduled')->count())
                ->badgeColor('warning'),
            'in_progress' => \Filament\Schemas\Components\Tabs\Tab::make('Dalam Proses')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'in_progress'))
                ->badge(\App\Models\Client::where('status', 'in_progress')->count())
                ->badgeColor('info'),
            'finished' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'finished'))
                ->badgeColor('success'),
            'canceled' => \Filament\Schemas\Components\Tabs\Tab::make('Dibatalkan')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'canceled'))
                ->badgeColor('danger'),
        ];
    }
}
