<?php

namespace App\Filament\Layanankkprl\Resources\Clients\Pages;

use App\Filament\Layanankkprl\Resources\Clients\ClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    use \Filament\Pages\Concerns\ExposesTableToWidgets;

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
            'my_clients' => \Filament\Schemas\Components\Tabs\Tab::make('Klien Saya')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereHas('assignments', fn ($q) => $q->where('user_id', auth()->id())))
                ->badge(\App\Models\Client::whereHas('assignments', fn ($q) => $q->where('user_id', auth()->id()))->count()),
            'waiting' => \Filament\Schemas\Components\Tabs\Tab::make('Menunggu')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'waiting'))
                ->badge(\App\Models\Client::where('status', 'waiting')->count()),
            'scheduled' => \Filament\Schemas\Components\Tabs\Tab::make('Dijadwalkan')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'scheduled'))
                ->badge(\App\Models\Client::where('status', 'scheduled')->count())
                ->badgeColor('warning'),
            'completed' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'completed'))
                ->badge(\App\Models\Client::where('status', 'completed')->count())
                ->badgeColor('success'),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Layanankkprl\Resources\Clients\Widgets\ClientStatsOverview::class,
        ];
    }
}
