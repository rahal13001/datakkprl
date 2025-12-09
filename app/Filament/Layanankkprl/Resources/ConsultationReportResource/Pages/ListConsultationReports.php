<?php

namespace App\Filament\Layanankkprl\Resources\ConsultationReportResource\Pages;

use App\Filament\Layanankkprl\Resources\ConsultationReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsultationReports extends ListRecords
{
    protected static string $resource = ConsultationReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua Laporan'),
            'my_reports' => \Filament\Schemas\Components\Tabs\Tab::make('Laporan Saya')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereHas('client.assignments', function ($q) {
                    $q->where('user_id', auth()->id());
                })),
        ];
    }
}
